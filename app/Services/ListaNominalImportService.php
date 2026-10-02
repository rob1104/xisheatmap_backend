<?php

namespace App\Services;

use App\Adapters\XlsxToCsvAdapter;
use App\Contracts\RowReaderInterface;
use App\Contracts\RowValidatorInterface;
use App\Models\ListaNominalCorte;
use App\Models\ListaNominalDetalle;
use App\Models\SeccionElectoral;
use App\Services\Readers\AdaptiveRowReader;
use App\Services\Readers\SplCsvRowReader;
use App\Validators\ListaNominalRowValidator\RowValidator;
use Illuminate\Support\Facades\DB;
use SplFileObject;
use RuntimeException;
use Throwable;

class ListaNominalImportService
{
    protected int $chunkSize = 500;
    protected RowValidatorInterface $validator;
    protected RowReaderInterface $reader;

    public function __construct(
        ?RowValidatorInterface $rowValidator = null,
        ?RowReaderInterface $rowReader = null
    )
    {
        // Resuelva mediante el contenedor de Laravel con fallback seguro para instanciación directa
        $this->validator = $rowValidator
            ?? (app()->bound(RowValidatorInterface::class)
            ? app(RowValidatorInterface::class)
            : new RowValidator());

        $this->reader = $rowReader
            ?? (app()->bound(RowReaderInterface::class)
            ? app(RowReaderInterface::class)
            : new AdaptiveRowReader(new SplCsvRowReader(), [new XlsxToCsvAdapter()]));
    }

    /**
     * Importa las cifras de Lista Nominal desde un archivo CSV hacia un corte específico.
     * 
     * @param ListaNominalCorte $corte
     * @param string $filePath Ruta absoluta al archivo en disco (.csv o .xlsx)
     * @param int $municipio Código del municipio (por defecto 41 - Victoria)
     * @return array Resumen del proceso
     * @throws Throwable
     */
    public function importFromCsv(ListaNominalCorte $corte, string $filePath, int $municipio = 41): array
    {
        
        //1. Obtiene y normaliza encabezados
        $rawHeaders = $this->reader->getHeaders($filePath);
        $normalizedHeaders = $this->normalizeHeaders($rawHeaders);
        $this->validateRequiredHeaders($normalizedHeaders);

        //2. Precargar en memoria el catálogo de secciones del municipio [codigo_seccion => id]
        $seccionesMap = SeccionElectoral::forMunicipio($municipio)
            ->pluck('id', 'seccion')
            ->mapWithKeys(fn ($id, $seccion) => [(string) (int) $seccion => $id])
            ->toArray();

        //3. Mapeo de secciones existentes preivas al corte
        $existingSeccionesInCorte = DB::table('lista_nominal_detalles')
            ->where('lista_nominal_corte_id', $corte->id)
            ->pluck('seccion_electoral_id')
            ->flip()
            ->toArray();
        
        $totalProcesadas = 0;
        $insertadas = 0;
        $actualizadas = 0;
        $filasInvalidas = 0;
        $filasSinPoligono = 0;
        $filasDuplicadas = 0;
        $seccionesSinPoligono = [];
        $seccionesProcesadasEnCorrida = [];
        $batch = [];

        DB::beginTransaction();

        try {
            foreach ($this->reader->readRows($filePath) as $row) {
                $totalProcesadas++;
                $attributes = $this->mapRowToAttributes($row, $normalizedHeaders);

                // Delegácion de validación a RowValidator
                $validation = $this->validator->validate($attributes);

                if (! $validation->isValid) {
                    $filasInvalidas++;
                    continue;
                }

                $data = $validation->sanitizedData;
                $seccionesKey = (string) $data['seccion'];

                // Si la sección no existe en la cartografia municipal, se clasifica sin polígono
                if (! isset($seccionesMap[$seccionesKey])) {
                    $filasSinPoligono++;
                    if (! in_array($seccionesKey, $seccionesSinPoligono, true)) {
                        $seccionesSinPoligono[] = $seccionesKey;
                    }
                    continue;
                }

                $seccionElectoralId = $seccionesMap[$seccionesKey];

                // Contabilización de registros únicos
                if (isset($seccionesProcesadasEnCorrida[$seccionElectoralId])) {
                    $filasDuplicadas++;
                } else {
                    $seccionesProcesadasEnCorrida[$seccionElectoralId] = true;
                    if (isset($existingSeccionesInCorte[$seccionElectoralId])) {
                        $actualizadas++;
                    } else {
                        $insertadas++;
                    }
                }

                // Indexa por sección para garantizar registros únicos dentro del mismo batch
                $batch[$seccionElectoralId] = [
                    'lista_nominal_corte_id' => $corte->id,
                    'seccion_electoral_id'   => $seccionElectoralId,
                    'total_lista_nominal'    => $data['total_lista_nominal'],
                    'padron_electoral'       => $data['padron_electoral'] ?? null,
                    'hombres'                => $data['hombres'] ?? null,
                    'mujeres'                => $data['mujeres'] ?? null,
                    'no_binario'             => $data['no_binario'] ?? 0,
                    'created_at'             => now(),
                    'updated_at'             => now(),
                ];


                if (count($batch) >= $this->chunkSize) {
                    $this->upsertBatch(array_values($batch));
                    $batch = [];
                }

            }

            //5. Procesar remanente
            if (!empty($batch)) {
                $this->upsertBatch(array_values($batch));
                $batch = [];
            }

            $filasOmitidas = $filasInvalidas + $filasSinPoligono;
            $registrosGuardados = $insertadas + $actualizadas;

            activity()
                ->performedOn($corte)
                ->withProperties([
                    'archivo'                 => basename($filePath),
                    'municipio'               => $municipio,
                    'total_procesadas'        => $totalProcesadas,
                    'insertadas'              => $insertadas,
                    'actualizadas'            => $actualizadas,
                    'registros_guardados'     => $registrosGuardados,
                    'filas_omitidas'          => $filasOmitidas,
                    'filas_invalidas'         => $filasInvalidas,
                    'filas_duplicadas'        => $filasDuplicadas,
                    'sin_poligono_geografico' => count($seccionesSinPoligono),
                ])
                ->log("Importación masiva de Lista Nominal completada para el corte ID: {$corte->id}");

            DB::commit();

            return [
                    'success'                 => true,
                    // Contrato requerido por el frontend (Index.vue)
                    'total_procesadas'        => $totalProcesadas,
                    'insertadas'              => $insertadas,
                    'actualizadas'            => $actualizadas,
                    'sin_poligono_geografico' => $seccionesSinPoligono,
                    'filas_omitidas'          => $filasOmitidas,
                    'filas_invalidas'         => $filasInvalidas,
                    'filas_duplicadas'        => $filasDuplicadas,
                    // Alias de compatibilidad retroactiva
                    'total_procesados'        => $totalProcesadas,
                    'total_guardados'         => $registrosGuardados,
                    'total_omitidos'          => $filasOmitidas,
                    'secciones_no_encontradas'=> $seccionesSinPoligono,
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
            // Eliminar BOM y caracteres invisibles
            $h = preg_replace('/[\x{FEFF}]/u', '', trim((string) $h));
            $h = mb_strtolower($h, 'UTF-8');
            // Normalizar acentos
            $h = str_replace(
                ['á', 'é', 'í', 'ó', 'ú'],
                ['a', 'e', 'i', 'o', 'u'],
                $h
            );
            $h = str_replace([' ', '-', '.'], '_', $h);

            return match ($h) {
                'seccion', 'sec'                                        => 'seccion',
                'lista_nominal', 'total_lista_nominal', 'ln', 'nominal' => 'total_lista_nominal',
                'padron', 'padron_electoral', 'pe'                      => 'padron_electoral',
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
        if (! in_array('seccion', $headers, true)) {
            throw new RuntimeException("El archivo no contiene la columna obligatoria 'seccion'.");
        }

        if (! in_array('total_lista_nominal', $headers, true)) {
            throw new RuntimeException("El archivo no contiene la columna obligatoria 'total_lista_nominal' o 'lista_nominal'.");
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