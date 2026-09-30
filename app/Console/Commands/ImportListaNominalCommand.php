<?php

namespace App\Console\Commands;

use App\Models\ListaNominalCorte;
use App\Services\ListaNominalImportService;
use DateTime;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * BE-4: Carga administrativa de Lista Nominal desde consola.
 *
 * Ejemplo:
 *   php artisan lista-nominal:import storage/app/ln_2024.csv --fecha=2024-03-31 --fuente="INE" --activar
 */
class ImportListaNominalCommand extends Command
{
    protected $signature = 'lista-nominal:import
        {archivo : Ruta al archivo CSV (absoluta o relativa a la raíz del proyecto)}
        {--fecha= : Fecha real del corte oficial (YYYY-MM-DD)}
        {--fuente=INE - Dirección Ejecutiva del Registro Federal de Electores : Fuente oficial del corte}
        {--descripcion= : Descripción opcional del corte}
        {--municipio= : Código de municipio (por defecto el configurado en spatial.default_municipio)}
        {--activar : Marcar el corte como activo al terminar la carga}';

    protected $description = 'Importa las cifras de Lista Nominal por sección desde un CSV y crea (o actualiza) el corte correspondiente';

    public function handle(ListaNominalImportService $importador): int
    {
        // ---------- Validaciones de entrada ----------
        $ruta = $this->resolverRuta((string) $this->argument('archivo'));
        if ($ruta === null) {
            $this->error("No se encontró o no se puede leer el archivo: {$this->argument('archivo')}");
            return self::FAILURE;
        }

        $fecha = (string) $this->option('fecha');
        if (! $this->esFechaValida($fecha)) {
            $this->error('Indica la fecha real del corte con --fecha=YYYY-MM-DD (ej. --fecha=2024-03-31).');
            return self::FAILURE;
        }

        $fuente = trim((string) $this->option('fuente'));
        if ($fuente === '' || mb_strlen($fuente) > 150) {
            $this->error('La fuente es obligatoria y debe tener como máximo 150 caracteres.');
            return self::FAILURE;
        }

        $descripcion = $this->option('descripcion');
        if ($descripcion !== null && mb_strlen($descripcion) > 255) {
            $this->error('La descripción debe tener como máximo 255 caracteres.');
            return self::FAILURE;
        }

        $municipio = (int) ($this->option('municipio') ?: config('spatial.default_municipio', 41));

        // ---------- Corte: se reutiliza si ya existe el mismo (fecha + fuente) ----------
        $corte = ListaNominalCorte::query()
            ->whereDate('fecha_corte', $fecha)
            ->where('fuente', $fuente)
            ->first();

        $corteNuevo = $corte === null;

        if ($corteNuevo) {
            $corte = ListaNominalCorte::create([
                'fecha_corte' => $fecha,
                'fuente' => $fuente,
                'descripcion' => $descripcion,
                'is_active' => false,
            ]);
            $this->info("Corte #{$corte->id} creado ({$fecha}).");
        } else {
            $this->warn("Ya existe el corte #{$corte->id} con esa fecha y fuente; sus cifras se actualizarán.");
            if ($descripcion !== null) {
                $corte->update(['descripcion' => $descripcion]);
            }
        }

        // ---------- Importación (BE-1) ----------
        $this->info('Importando ' . basename($ruta) . " para el municipio {$municipio}...");

        try {
            $resumen = $importador->importFromCsv($corte, $ruta, $municipio);
        } catch (Throwable $e) {
            // No dejar un corte vacío si se creó en esta ejecución
            if ($corteNuevo) {
                $corte->delete();
                $this->line("Se eliminó el corte #{$corte->id} creado en esta ejecución.");
            }
            $this->error('La importación falló: ' . $e->getMessage());
            return self::FAILURE;
        }

        $noEncontradas = $resumen['secciones_no_encontradas'] ?? [];

        $this->table(['Métrica', 'Valor'], [
            ['Corte', "#{$corte->id} · {$fecha} · {$fuente}"],
            ['Filas procesadas', $resumen['total_procesados'] ?? 0],
            ['Secciones guardadas', $resumen['total_guardados'] ?? 0],
            ['Secciones sin polígono (omitidas)', $resumen['total_omitidos'] ?? count($noEncontradas)],
        ]);

        if (! empty($noEncontradas)) {
            $muestra = array_slice($noEncontradas, 0, 20);
            $resto = count($noEncontradas) - count($muestra);
            $this->warn('Secciones del archivo que no existen en la cartografía: '
                . implode(', ', $muestra) . ($resto > 0 ? " y {$resto} más" : ''));
        }

        // ---------- Activación opcional ----------
        if ($this->option('activar')) {
            if ($corte->detalles()->count() === 0) {
                $this->error('El corte no tiene secciones cargadas; no se activó.');
                return self::FAILURE;
            }

            DB::transaction(function () use ($corte) {
                ListaNominalCorte::query()
                    ->where('is_active', true)
                    ->whereKeyNot($corte->id)
                    ->update(['is_active' => false]);

                $corte->update(['is_active' => true]);
            });

            $this->info("Corte #{$corte->id} activado: el mapa ya usa esta Lista Nominal.");
        } elseif (! $corte->fresh()->is_active) {
            $this->line('El corte no está activo. Actívalo con --activar o desde el panel.');
        }

        return self::SUCCESS;
    }

    protected function resolverRuta(string $archivo): ?string
    {
        foreach ([$archivo, base_path($archivo)] as $candidato) {
            if (is_file($candidato) && is_readable($candidato)) {
                return realpath($candidato) ?: $candidato;
            }
        }

        return null;
    }

    protected function esFechaValida(string $fecha): bool
    {
        $dt = DateTime::createFromFormat('Y-m-d', $fecha);

        return $dt !== false && $dt->format('Y-m-d') === $fecha;
    }
}
