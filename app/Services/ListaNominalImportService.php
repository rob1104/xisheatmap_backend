<?php

namespace App\Services;

use App\Models\ListaNominalCorte;
use App\Models\ListaNominalDetalle;
use App\Models\SeccionElectoral;
use Illuminate\Support\Facades\DB;
use SplFileObject;
use RuntimeException;
use Throwable;

class ListaNominalImportService
{
    protected int $chunkSize = 500;

    /**
     * Importa las cifras de Lista Nominal desde un archivo CSV hacia un corte específico.
     * 
     * @param ListaNominalCorte $corte
     * @param string $filePath Ruta absoluta al archivo en disco
     * @param int $municipio Código del municipio (por defecto 41 - Victoria)
     * @return array Resumen del proceso
     */
    public function importFromCsv(ListaNominalCorte $corte, string $filePath, int $municipio = 41): array
    {
        if (! file_exists($filePath) || ! is_readable($filePath)) {
            throw new RuntimeException("El archivo no existe o no se puede leer: {$filePath}");
        }

        $file = new SplFileObject($filePath, 'r');
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);

        // Detectar delimitador de la cabecera
        $firstLine = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)[0] ?? '';
        $delimiter = str_contains($firstLine, ';') ? ';' : ',';
        $file->setCsvControl($delimiter);

        // Obtener y normalizar encabezados
        $header = $file->fgetcsv();
        if (!$header || empty(array_filter($header))) {
            throw new RuntimeException('El archivo CSV se encuentra vacío o no contiene encabezados válidos.');
        }

        $normalizedHeaders = $this->normalizeHeaders($header);
        $this->validateRequiredHeaders($normalizedHeaders);

        // Precargar en memoria el catálogo de secciones del municipio [codigo_seccion => id]
        $seccionesMap = SeccionElectoral::forMunicipio($municipio)
            ->pluck('id', 'seccion')
            ->mapWithKeys(fn ($id, $seccion) => [(string) (int) $seccion => $id])
            ->toArray();
        
        $totalProcesados = 0;
        $totalGuardados = 0;
        $seccionesNoEncontradas = [];
        $batch = [];

        DB::beginTransaction();

        try {
            while (!$file->eof()) {
                $row = $file->fgetcsv();
                if (!$row || empty(array_filter($row))) { 
                    continue;
                }

                $totalProcesados++;
                $data = $this->mapRowToAttributes($row, $normalizedHeaders);

                $seccionKey = (string) (int) ($data['seccion'] ?? 0);

                // Si la sección no existe en la cartografía municipal, se agrega a omitidos
                if (!isset($seccionesMap[$seccionKey])) {
                    if (!in_array($data['seccion'], $seccionesNoEncontradas)) {
                        $seccionesNoEncontradas[] = (string) $data['seccion'];
                    }
                    continue;
                }

                $batch[] = [
                    'lista_nominal_corte_id' => $corte->id,
                    'seccion_electoral_id'   => $seccionesMap[$seccionKey],
                    'total_lista_nominal'    => (int) ($data['total_lista_nominal'] ?? 0),
                    'padron_electoral'       => isset($data['padron_electoral']) ? (int) $data['padron_electoral'] : null,
                    'hombres'                => isset($data['hombres']) ? (int) $data['hombres'] : null,
                    'mujeres'                => isset($data['mujeres']) ? (int) $data['mujeres'] : null,
                    'no_binario'             => (int) ($data['no_binario'] ?? 0),
                    'created_at'             => now(),
                    'updated_at'             => now(),
                ];

                if (count($batch) >= $this->chunkSize) {
                    $this->upsertBatch($batch);
                    $totalGuardados += count($batch);
                    $batch = [];
                }
            }

            // Procesar lote remanente
            if (!empty($batch)) {
                $this->upsertBatch($batch);
                $totalGuardados += count($batch);
                $batch = [];
            }

            // Registrar en ActivityLog
            activity()
                ->performedOn($corte)
                ->withProperties([
                    'archivo'                  => basename($filePath),
                    'municipio'                => $municipio,
                    'total_procesados'         => $totalProcesados,
                    'total_guardados'          => $totalGuardados,
                    'secciones_no_encontradas' => count($seccionesNoEncontradas),
                ])
                ->log("Importación masiva de Lista Nominal completada para el corte ID: {$corte->id}");

            DB::commit();

            return [
                'success'                  => true,
                'total_procesados'         => $totalProcesados,
                'total_guardados'          => $totalGuardados,
                'total_omitidos'           => count($seccionesNoEncontradas),
                'secciones_no_encontradas' => $seccionesNoEncontradas,
            ];
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Realiza Upsert de un lote en lista_nominal_detalles.
     */
    protected function upsertBatch(array $batch): void
    {
        ListaNominalDetalle::upsert(
            $batch,
            ['lista_nominal_corte_id', 'seccion_electoral_id'],
            ['total_lista_nominal', 'padron_electoral', 'hombres', 'mujeres', 'no_binario', 'updated_at']
        );
    }

    /**
     * Limpia y normaliza los encabezados del CSV.
     */
    protected function normalizeHeaders(array $headers): array
    {
        return array_map(function ($h) {
            // Eliminar BOM y caracteres no imprimibles
            $h = preg_replace('/[\x{FEFF}]/u', '', trim((string)$h));
            $h = mb_strtolower($h, 'UTF-8');
            $h = str_replace([' ', '-', '.'], '_', $h);

            return match ($h) {
                'seccion', 'sección', 'sec'                             => 'seccion',
                'lista_nominal', 'total_lista_nominal', 'ln', 'nominal' => 'total_lista_nominal',
                'padron', 'padrón', 'padron_electoral', 'pe'            => 'padron_electoral',
                'hombres', 'masculino', 'h'                             => 'hombres',
                'mujeres', 'femenino', 'm'                              => 'mujeres',
                'no_binario', 'nobinario', 'no_binarios'                => 'no_binario',
                default                                                 => $h,
            };
        }, $headers);
    }

    /**
     * Valida que existan al menos las columnas indispensables: seccion y total_lista_nominal.
     */
    protected function validateRequiredHeaders(array $headers): void
    {
        if (!in_array('seccion', $headers)) {
            throw new RuntimeException("El archivo CSV no contiene la columna obligatoria 'seccion'.");
        }

        if (!in_array('total_lista_nominal', $headers)) {
            throw new RuntimeException("El archivo CSV no contiene la columna obligatoria 'total_lista_nominal' o 'lista_nominal'.");
        }
    }

    /**
     * Mapea los valores de la fila a los encabezados normalizados.
     */
    protected function mapRowToAttributes(array $row, array $headers): array
    {
        $data = [];
        foreach ($headers as $index => $key) {
            $data[$key] = isset($row[$index]) ? trim((string)$row[$index]) : null;
        }

        return $data;
    }
}