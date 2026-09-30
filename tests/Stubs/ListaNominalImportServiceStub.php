<?php

namespace Tests\Stubs;

use App\Models\ListaNominalCorte;
use App\Models\ListaNominalDetalle;
use App\Models\SeccionElectoral;
use ZipArchive;

class ListaNominalImportServiceStub
{
    public function importFromCsv(ListaNominalCorte $corte, string $filePath, int $municipio = 41): array
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $rows = [];

        if (in_array($extension, ['xlsx', 'xlsm'], true)) {
            $rows = $this->readXlsx($filePath);
        } else {
            $rows = $this->readCsv($filePath);
        }

        $guardados = 0;
        foreach ($rows as $data) {
            if (empty($data['seccion']) || empty($data['total_lista_nominal'])) {
                continue;
            }

            $seccion = SeccionElectoral::where('municipio', $municipio)
                ->where('seccion', (string) $data['seccion'])
                ->first();

            if ($seccion) {
                ListaNominalDetalle::updateOrCreate(
                    [
                        'lista_nominal_corte_id' => $corte->id,
                        'seccion_electoral_id' => $seccion->id,
                    ],
                    [
                        'total_lista_nominal' => (int) $data['total_lista_nominal'],
                        'padron_electoral' => isset($data['padron_electoral']) ? (int) $data['padron_electoral'] : null,
                        'hombres' => isset($data['hombres']) ? (int) $data['hombres'] : null,
                        'mujeres' => isset($data['mujeres']) ? (int) $data['mujeres'] : null,
                        'no_binario' => isset($data['no_binario']) ? (int) $data['no_binario'] : 0,
                    ]
                );
                $guardados++;
            }
        }

        return [
            'success' => true,
            'total_procesadas' => count($rows),
            'insertadas' => $guardados,
            'actualizadas' => 0,
            'sin_poligono_geografico' => [],
            'filas_omitidas' => 0,
            'filas_invalidas' => 0,
            'filas_duplicadas' => 0,
            'total_procesados' => count($rows),
            'total_guardados' => $guardados,
            'total_omitidos' => 0,
            'secciones_no_encontradas' => [],
        ];
    }

    protected function readCsv(string $filePath): array
    {
        $rows = [];
        if (! file_exists($filePath) || ($handle = fopen($filePath, 'r')) === false) {
            return $rows;
        }

        $headers = fgetcsv($handle);
        if (! $headers) {
            fclose($handle);

            return $rows;
        }

        $headers = array_map(fn ($h) => trim(strtolower((string) $h)), $headers);

        while (($data = fgetcsv($handle)) !== false) {
            $row = [];
            foreach ($headers as $i => $h) {
                $row[$h] = $data[$i] ?? null;
            }
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    protected function readXlsx(string $filePath): array
    {
        $rows = [];
        if (! file_exists($filePath)) {
            return $rows;
        }

        $zip = new ZipArchive;
        if ($zip->open($filePath) !== true) {
            return $rows;
        }

        $sharedStrings = [];
        $ssXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($ssXml) {
            $xml = simplexml_load_string($ssXml);
            if ($xml && isset($xml->si)) {
                foreach ($xml->si as $si) {
                    $sharedStrings[] = (string) $si->t;
                }
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml) {
            $xml = simplexml_load_string($sheetXml);
            if ($xml && isset($xml->sheetData->row)) {
                $sheetRows = $xml->sheetData->row;
                $headers = [];

                if (isset($sheetRows[0])) {
                    foreach ($sheetRows[0]->c as $c) {
                        $val = (string) $c->v;
                        if ((string) $c['t'] === 's' && isset($sharedStrings[(int) $val])) {
                            $val = $sharedStrings[(int) $val];
                        }
                        $headers[] = trim(strtolower((string) $val));
                    }
                }

                for ($i = 1; $i < count($sheetRows); $i++) {
                    $row = [];
                    $colIdx = 0;
                    foreach ($sheetRows[$i]->c as $c) {
                        $val = (string) $c->v;
                        if ((string) $c['t'] === 's' && isset($sharedStrings[(int) $val])) {
                            $val = $sharedStrings[(int) $val];
                        }
                        $colName = $headers[$colIdx] ?? $colIdx;
                        $row[$colName] = $val;
                        $colIdx++;
                    }
                    $rows[] = $row;
                }
            }
        }

        $zip->close();

        return $rows;
    }
}
