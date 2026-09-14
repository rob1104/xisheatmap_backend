<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SeccionesVictoriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = database_path('seeders/data/secciones_victoria.geojson');

        if (! file_exists($jsonPath)) {
            $this->command->error("No se encontró el archivo GeoJSON en: {$jsonPath}");
            return;
        }

        $this->command->info("Cargando archivo GeoJSON: {$jsonPath}");
        $content = file_get_contents($jsonPath);
        $geojson = json_decode($content, true);

        if (! isset($geojson['features'])) {
            $this->command->error("Estructura GeoJSON inválida (falta la clave 'features').");
            return;
        }

        $features = $geojson['features'];
        $total = count($features);
        $this->command->info("Procesando {$total} secciones electorales...");

        $inserted = 0;
        $updated = 0;

        DB::beginTransaction();

        try {
            foreach ($features as $feature) {
                $props = $feature['properties'] ?? [];
                $geom = $feature['geometry'] ?? [];

                $seccion = (string) ($props['seccion'] ?? $props['SECCION'] ?? '');
                $entidad = (int) ($props['entidad'] ?? 28);
                $municipio = (int) ($props['municipio'] ?? 41);
                $distritoF = isset($props['distrito_f']) ? (int) $props['distrito_f'] : null;
                $distritoL = isset($props['distrito_l']) ? (int) $props['distrito_l'] : null;
                $tipo = isset($props['tipo']) ? (int) $props['tipo'] : null;
                $control = isset($props['control']) ? (int) $props['control'] : null;

                if (empty($seccion)) {
                    continue;
                }

                $geomJson = json_encode($geom);

                // Insertamos o actualizamos usando SQL nativo para asignar el campo GEOMETRY con SRID 4326
                $affected = DB::statement("
                    INSERT INTO secciones_electorales 
                        (entidad, municipio, seccion, distrito_federal, distrito_local, tipo, control, poligono, created_at, updated_at)
                    VALUES 
                        (?, ?, ?, ?, ?, ?, ?, ST_GeomFromGeoJSON(?, 1, 4326), NOW(), NOW())
                    ON DUPLICATE KEY UPDATE
                        distrito_federal = VALUES(distrito_federal),
                        distrito_local = VALUES(distrito_local),
                        tipo = VALUES(tipo),
                        control = VALUES(control),
                        poligono = VALUES(poligono),
                        updated_at = NOW()
                ", [
                    $entidad,
                    $municipio,
                    $seccion,
                    $distritoF,
                    $distritoL,
                    $tipo,
                    $control,
                    $geomJson,
                ]);

                if ($affected === 1) {
                    $inserted++;
                } else {
                    $updated++;
                }
            }

            DB::commit();
            $this->command->info("¡Éxito! Se procesaron {$total} secciones electorales ({$inserted} insertadas, {$updated} actualizadas).");

        } catch (\Throwable $e) {
            DB::rollBack();
            $this->command->error("Error durante la inserción: " . $e->getMessage());
            throw $e;
        }
    }
}
