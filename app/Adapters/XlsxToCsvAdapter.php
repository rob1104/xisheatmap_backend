<?php

namespace App\Adapters;

use App\Contracts\FileToCsvAdapterInterface;
use RuntimeException;
use XMLReader;
use ZipArchive;

class XlsxToCsvAdapter implements FileToCsvAdapterInterface 
{
    public function supports(string $filename) : bool
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($extension, ["xlsx","xlsm"], true);
    }

    public function convertToCsv(string $sourceFilePath) : string
    {
        if (! file_exists($sourceFilePath) || ! is_readable($sourceFilePath)) {
            throw new RuntimeException("No se puede leer el archivo XLSX: {$sourceFilePath}");
        }

        $zip = new ZipArchive();
        if ($zip->open($sourceFilePath) !== true) {
            throw new RuntimeException("El archivo no es un documento XLSX válido o está dañado");
        }

        try{
            // Cargar la tabla de cadenas compartidas (sharedString.xml)
            $sharedStrings = $this->loadSharedStrings($zip);

            // Localizar la primera hoja de cálculo (generalmente sheet1.xml)
            $sheetXmlContent = $zip->getFromName("xl/worksheets/sheet1.xml");
            if ($sheetXmlContent === false) {
                throw new RuntimeException("No se encontró la hoja de cálculo principal (sheet1) en el archivo XLSX.");
            }

            // Crear archivo CSV temporal en el directorio de temporales del sistema
            $tempCSVPath = tempnam(sys_get_temp_dir(), "xlxs_to_csv_") . '.csv';
            $csvHandle = fopen($tempCSVPath, 'w');
            if ($csvHandle === false) {
                throw new RuntimeException('No se pudo crear el archivo temporal para la conversión a CSV.');
            }

            // Parsear la hoja en streaming con XMLReader y escribir directamente al CSV
            $this->streamSheetToCsv($sheetXmlContent, $sharedStrings, $csvHandle);

            fclose($csvHandle);
            return $tempCSVPath;

        } catch (RuntimeException $e) {
            throw new RuntimeException("Ha ocurrido un error",0, $e);
        } finally {
            $zip->close();
        }
    }

    /**
     * Extrae en memoria el diccionario de strings compartidos de Excel.
     */
    protected function loadSharedStrings(ZipArchive $zip) : array
    {
        $content = $zip->getFromName('xl/sharedStrings.xml');
        if ($content === false) {
            return []; // El archivo no utiliza cadenas compartidas (solo números o inlineStrings)
        }

        $xml = XMLReader::XML($content);
        $strings = [];
        $currentText = '';
        $isInsideT = false;

        while ($xml->read()) {
            if ($xml->nodeType === XMLReader::ELEMENT && $xml->name === 'si') {
                $currentText = '';
            } elseif ($xml->nodeType === XMLReader::ELEMENT && $xml->name === 't') {
                $isInsideT = true;
            } elseif ($xml->nodeType === XMLReader::TEXT && $isInsideT) {
                $currentText .= $xml->value;
            } elseif ($xml->nodeType === XMLReader::END_ELEMENT && $xml->name === 't') {
                $isInsideT = false;
            } elseif ($xml->nodeType === XMLReader::END_ELEMENT && $xml->name === 'si') {
                $strings[] = $currentText;
            }
        }

        $xml->close();
        return $strings;
    }

    /**
     * Lee las filas y celdas de hoja y las escribe en el archivo CSV de salida.
     */
    protected function streamSheetToCsv(string $sheetXmlContent, array $sharedStrings, $csvHandle) : void
    {
        
        $xml = XMLReader::XML($sheetXmlContent);
        $currentRow = [];
        $currentCellRef = [];
        $currentValue = '';
        $isReadingValue = true;
        $maxColIndex = 0;

        while ($xml->read()) {
            if ($xml->nodeType === XMLReader::ELEMENT && $xml->name === 'row') {
                $currentRow = [];
                $maxColIndex = 0;
            } else if ($xml->nodeType === XMLReader::ELEMENT && $xml->name === 'c') {
                $currentCellRef = $xml->getAttribute('r') ?? '';
                $currentCellType = $xml->getAttribute('t') ?? '';
                $currentValue = '';
            } else if ($xml->nodeType === XMLReader::ELEMENT && ($xml->name === 'v' || $xml->name === 't')) {
                $isReadingValue = true;
            } else if ($xml->nodeType === XMLReader::TEXT  && $isReadingValue) {
                $currentValue .= $xml->value;
            } else if ($xml->nodeType === XMLReader::END_ELEMENT && $xml->name === 'c') {
                $colIndex = $this->columnLetterToIndex($currentCellRef);

                // Resolver si es string compartido o valor directo
                if ($currentCellType === 's' && isset($sharedStrings[(int) $currentValue])) {
                    $finalVal = $sharedStrings[(int) $currentValue];
                } else {
                    $finalVal = $currentValue;
                }

                $currentRow[$colIndex] = $finalVal;
                if ($colIndex > $maxColIndex) {
                    $maxColIndex = $colIndex;
                }
            } else if ($xml->nodeType === XMLReader::END_ELEMENT && $xml->name === 'row') {
                if (!empty($currentRow)) {
                    // Rellena columnas vacías intermedias
                    $normalizedRow = [];
                    for ($i = 0; $i <= $maxColIndex; $i++) {
                        $normalizedRow[$i] = $currentRow[$i] ?? '';
                    }
                    fputcsv($csvHandle, $normalizedRow, ',', '"', "\\");
                }
            }
        }

        $xml->close();
    }

    /**
     * Convierte una referencia de celda tipo "A1", "BC12" al índice base 0 de columna (A=0, B=1, ...).
     */
    protected function columnLetterToIndex(string $cellRef) : int 
    {
        $letters = preg_replace('/[0-9]/','',strtoupper($cellRef));
        $index = 0;
        $len = strlen($letters);

        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($letters[$i]) - ord('A') + 1);
        }

        return max(0, $index - 1);
    }
}