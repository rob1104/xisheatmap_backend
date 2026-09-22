<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Carbon\Carbon;

class IneSeeder2 extends Seeder
{
    public function run(): void
    {
        // Usamos Faker en español de México
        $faker = Faker::create('es_MX');

        $records = [];
        $now = Carbon::now();

        // Colonias reales y comunes de Ciudad Victoria para la demo
        $colonias = [
            'Centro', 'Mainero', 'Tamatán', 'Libertad',
            'Pedro Sosa', 'Estudiantil', 'Las Flores', 'Adolfo López Mateos',
            'Amalia G. de Castillo Ledón', 'Fuego Nuevo', 'Teocaltiche', 'Corregidora'
        ];

        // Obtenemos las secciones electorales reales de Victoria con sus centroides espaciales
        $seccionesVictoria = \App\Models\SeccionElectoral::where('municipio', 41)
            ->selectRaw('seccion, ST_Y(ST_Centroid(poligono)) as lat, ST_X(ST_Centroid(poligono)) as lng')
            ->get()
            ->keyBy(fn($item) => (string)$item->seccion);

        $seccionCodes = $seccionesVictoria->keys()->map(fn($k) => (string)$k)->toArray();
        $userId = \App\Models\User::first()?->id ?? 1;

        for ($i = 0; $i < 700; $i++) {
            // Clave de Elector simulada
            $claveElector = strtoupper(
                $faker->lexify('??????') .
                $faker->numerify('######') .
                $faker->lexify('?') .
                $faker->numerify('###')
            );

            // Sección original impresa en la credencial
            $seccionCredencial = !empty($seccionCodes)
                ? (string)$faker->randomElement($seccionCodes)
                : (string)$faker->numberBetween(1563, 2264);

            // Simulación realista: 85% vive y se captura en su sección, 10% discrepancia, 5% fuera de cobertura
            if (!empty($seccionCodes) && isset($seccionesVictoria[$seccionCredencial])) {
                $pct = $faker->numberBetween(1, 100);

                if ($pct <= 85) {
                    // 1. Coincidente: GPS dentro de la sección de su credencial (pequeño offset del centroide)
                    $lat = (float)$seccionesVictoria[$seccionCredencial]->lat + $faker->randomFloat(6, -0.0003, 0.0003);
                    $lng = (float)$seccionesVictoria[$seccionCredencial]->lng + $faker->randomFloat(6, -0.0003, 0.0003);
                } elseif ($pct <= 95) {
                    // 2. Discrepancia: Ciudadano con credencial en una sección pero capturado en otra sección distinta
                    $otraSec = (string)$faker->randomElement(array_values(array_diff($seccionCodes, [$seccionCredencial])));
                    $lat = (float)($seccionesVictoria[$otraSec]->lat ?? 23.73) + $faker->randomFloat(6, -0.0003, 0.0003);
                    $lng = (float)($seccionesVictoria[$otraSec]->lng ?? -99.14) + $faker->randomFloat(6, -0.0003, 0.0003);
                } else {
                    // 3. Fuera de cobertura: Captura realizada fuera del polígono municipal de Victoria
                    $lat = $faker->randomFloat(6, 24.350000, 24.700000);
                    $lng = $faker->randomFloat(6, -99.250000, -99.050000);
                }
            } else {
                $lat = $faker->randomFloat(6, 23.680000, 23.780000);
                $lng = $faker->randomFloat(6, -99.180000, -99.080000);
            }

            $records[] = [
                'clave_elector'    => $claveElector,
                'curp'             => $claveElector,
                'ocr_numero'       => $faker->numerify('#############'),
                'nombre'           => $faker->firstName(),
                'apellido_paterno' => $faker->lastName(),
                'apellido_materno' => $faker->lastName(),
                'fecha_nacimiento' => $faker->dateTimeBetween('-70 years', '-18 years')->format('Y-m-d'),
                'sexo'             => $faker->randomElement(['H', 'M']),
                'calle_numero'     => $faker->streetName() . ' ' . $faker->buildingNumber(),
                'colonia'          => $faker->randomElement($colonias),
                'codigo_postal'    => $faker->numberBetween(87000, 87099), // CP de Victoria
                'municipio'        => 'VICTORIA',
                'estado'           => 'TAMPS',
                'seccion'          => $seccionCredencial,
                'seccion_gps'      => null, // Se asigna mediante el servicio espacial
                'vigencia'         => (string) $faker->numberBetween(2024, 2034),
                'latitud'          => $lat,
                'longitud'         => $lng,
                'user_id'          => $userId,
                'capturado_en'     => $faker->dateTimeBetween('-1 month', 'now')->format('Y-m-d H:i:s'),
                'created_at'       => $now,
                'updated_at'       => $now,
            ];
        }

        // Insertamos en la base de datos en bloques de 100 (Chunking) para no saturar la memoria
        $chunks = array_chunk($records, 100);

        foreach ($chunks as $chunk) {
            DB::table('ine_records')->insert($chunk);
        }

        // Sincronizamos territorialmente conservando la sección original y asignando seccion_gps
        $this->command->info('Sincronizando secciones electorales según coordenadas GPS reales (force: false)...');
        $res = app(\App\Contracts\SpatialServiceInterface::class)->assignSeccionToIneRecords(force: false);

        $this->command->info("¡700 expedientes generados! Asignados por GPS: {$res['asignados']}, Fuera de cobertura: {$res['sin_cobertura']}");
    }
}
