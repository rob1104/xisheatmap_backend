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

        for ($i = 0; $i < 700; $i++) {
            // Clave de Elector simulada
            $claveElector = strtoupper(
                $faker->lexify('??????') .
                $faker->numerify('######') .
                $faker->lexify('?') .
                $faker->numerify('###')
            );

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
                'seccion'          => (string) $faker->numberBetween(1000, 1500),
                'vigencia'         => (string) $faker->numberBetween(2024, 2034),

                // Coordenadas estrictas para la mancha urbana de Ciudad Victoria
                // Latitud: entre 23.6800 (Sur) y 23.7800 (Norte)
                // Longitud: entre -99.1800 (Oeste) y -99.0800 (Este)
                'latitud'          => $faker->randomFloat(6, 23.680000, 23.780000),
                'longitud'         => $faker->randomFloat(6, -99.180000, -99.080000),

                'user_id'          => 1,
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

        $this->command->info('¡700 expedientes ficticios generados en Ciudad Victoria exitosamente!');
    }
}
