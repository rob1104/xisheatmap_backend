<?php

namespace App\Services\Readers;

use App\Contracts\FileToCsvAdapterInterface;
use App\Contracts\RowReaderInterface;
use Generator;

class AdaptiveRowReader implements RowReaderInterface
{
    /**
     * @param RowReaderInterface $csvReader Lector base para CSV
     * @param array <FileToCsvAdapterInterface> $adapters Adaptadores registrados (ej XlsxCsvAdapter)
    */
    public function __construct(
        protected RowReaderInterface $csvReader,
        protected array $adapters = []
    ){}

    public function getHeaders (string $filePath) : array
    {
        [$effectivePath, $isTemporary] = $this->resolveFilePath($filePath);

        try {
            return $this->csvReader->getHeaders($effectivePath);
        } finally {
            if ($isTemporary && file_exists($effectivePath)) {
                @unlink($effectivePath);
            }
        }
    }

    public function readRows (string $filePath) : Generator
    {
        [$effectivePath, $isTemporary] = $this->resolveFilePath($filePath);
        try {
            foreach ($this->csvReader->readRows($effectivePath) as $row) {
                yield $row;
            }
        } finally {
            if ($isTemporary && file_exists($effectivePath)) {
                @unlink($effectivePath);
            }
        }
    }

    /**
     * Si el archivo necesita conversión (ej. XLSX), lo convierte y marca como temporal
     * Si ya es CSV, lo devuelve tal cual.
     * 
     * @return array {0: string, 1: bool} [rutaDelArchivo, esTemporal]
     */
    protected function resolveFilePath (string $filePath) : array
    {
        foreach ($this->adapters as $adapter) {
            if ($adapter->supports($filePath)) {
                $tempCsvPath = $adapter->convertToCsv($filePath);
                return [$tempCsvPath, true];
            }
        }
        
        return [$filePath, false];
    }
    
}