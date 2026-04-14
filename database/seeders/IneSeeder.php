<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Carbon\Carbon;

class IneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Configuramos Faker en español de México
        $faker = Faker::create('es_MX');

        $records = [];
        $now = Carbon::now();

        // Lista de colonias comunes en Matamoros para darle realismo
        $colonias = [
            'Centro', 'Buenavista', 'San Francisco', 'Las Brisas',
            'Puerto Rico', 'Modelo', 'Valle Hermoso', 'Mariano Matamoros',
            'Expofiesta Sur', 'Los Ebanos', 'Arboledas', 'Lauro Villar'
        ];

        for ($i = 0; $i < 500; $i++) {
            // Generar una Clave de Elector simulada (18 caracteres)
            $claveElector = strtoupper(
                $faker->lexify('??????') .
                $faker->numerify('######') .
                $faker->lexify('?') .
                $faker->numerify('###')
            );

            $records[] = [
                'clave_elector'    => $claveElector,
                'curp'             => $claveElector, // Simplificado para la demo
                'ocr_numero'       => $faker->numerify('#############'),
                'nombre'           => $faker->firstName(),
                'apellido_paterno' => $faker->lastName(),
                'apellido_materno' => $faker->lastName(),
                'fecha_nacimiento' => $faker->dateTimeBetween('-70 years', '-18 years')->format('Y-m-d'),
                'sexo'             => $faker->randomElement(['H', 'M']),
                'calle_numero'     => $faker->streetName() . ' ' . $faker->buildingNumber(),
                'colonia'          => $faker->randomElement($colonias),
                'codigo_postal'    => $faker->numberBetween(87300, 87499), // Códigos postales de la zona
                'municipio'        => 'MATAMOROS',
                'estado'           => 'TAMPS',
                'seccion'          => (string) $faker->numberBetween(400, 999),
                'vigencia'         => (string) $faker->numberBetween(2024, 2034),

                // Coordenadas estrictas para el área de Matamoros, Tamaulipas
                // Latitud: entre 25.8000 y 25.9200
                // Longitud: entre -97.5800 y -97.4200
                'latitud'          => $faker->randomFloat(6, 25.800000, 25.920000),
                'longitud'         => $faker->randomFloat(6, -97.580000, -97.420000),

                'user_id'          => 1, // Asignado al administrador principal
                'capturado_en'     => $faker->dateTimeBetween('-1 month', 'now')->format('Y-m-d H:i:s'),
                'created_at'       => $now,
                'updated_at'       => $now,
            ];
        }

        // Insertamos en la base de datos en bloques de 100 para optimizar el rendimiento
        $chunks = array_chunk($records, 100);

        foreach ($chunks as $chunk) {
            // Nota: Asegúrate de que el nombre de la tabla coincida con el tuyo ('ine_records')
            DB::table('ine_records')->insert($chunk);
        }

        $this->command->info('¡500 expedientes ficticios generados en Matamoros exitosamente!');
    }
}
