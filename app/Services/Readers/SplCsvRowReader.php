<?php

namespace App\Services\Readers;

use App\Contracts\RowReaderInterface;
use Generator;
use RuntimeException;
use SplFileObject;

class SplCsvRowReader implements RowReaderInterface
{
    public function getHeaders(string $filePath) : array
    {
        $file = $this->openFile($filePath);
        $headers = $file->fgetcsv();

        if (! $headers || empty(array_filter($headers))) {
            throw new RuntimeException("El archivo CSV se encuentra vacío o no contiene encabezados válidos");
        }

        return $headers;
    }

    public function readRows (string $filePath) : Generator
    {
        $file = $this->openFile($filePath);

        // Omitir cabecera
        $file->fgetcsv();

        while (! $file->eof()) {
            $row = $file->fgetcsv();

            if (! $row || empty(array_filter($row))) {
                continue;
            }

            yield $row;
        }
    }

    protected function openFile(string $filePath) : SplFileObject
    {
        if (! file_exists($filePath) || ! is_readable($filePath)) {
            throw new RuntimeException("El archivo no existe o no se puede leer {$filePath}");
        }

        $file = new SplFileObject($filePath,"r");
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);

        // Deteccíon automática de coma o punta y coma
        $firstLine = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)[0] ?? '';
        $delimiter = str_contains($firstLine, ";") ? ';' : ',';
        $file->setCsvControl($delimiter, '"', "\\");

        return $file;
    }
}