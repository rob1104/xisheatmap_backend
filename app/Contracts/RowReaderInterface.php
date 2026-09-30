<?php

namespace App\Contracts;

use Generator;

interface RowReaderInterface
{
    /**
     * Abre el archivo y retorna los encabezados limpios/normalizados.
     */

    public function getHeaders(string $filePath): array;

    /**
     * Itera línea por línea entregando los datos crudos de cada fila
     * @return Generator<int, array>
     */

    public function readRows(string $filePath): Generator;
}