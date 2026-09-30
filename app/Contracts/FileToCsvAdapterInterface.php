<?php

namespace App\Contracts;

interface FileToCsvAdapterInterface
{
    /**
     * Determina si el adaptador puede procesar la extensión del archivo
     */
    public function supports(string $filename): bool;

    /**
     * Convierte el archivo origen a un archivo CSV temporal y retorna su ruta absoluta
     * 
     * @param string $sourceFilePath Ruta al archivo original
     * @return string Ruta absoluta al archivo temporal generado
     * @throws \RuntimeException Si el archivo está corrupto o no se puede procesar
     */
    public function convertToCsv(string $sourceFilePath): string;
}