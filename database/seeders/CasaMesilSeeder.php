<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CasaMesil;

class CasaMesilSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $json = file_get_contents(database_path('data/casas_mesil.json'));
        $data = json_decode($json, true);

        foreach ($data as $item) {
            CasaMesil::create([
                'nombre' => $item['Nombre'] ?? null,
                'curp' => $item['Curp'] ?? null,
                'telefono' => $item['Teléfono'] ?? null,
                'direccion' => $item['Dirección'] ?? null,
                'zona' => $item['Zona'] ?? null,
                'estado' => $item['Estado'] ?? null,
                'municipio' => $item['Municipio'] ?? null,
                'distrito' => $item['Distrito'] ?? null,
                'seccion' => $item['Sección'] ?? null,
                'manzana' => $item['Manzana'] ?? null,
                'latitud' => $item['latitud'] ?? null,
                'longitud' => $item['longitud'] ?? null,
            ]);
        }
    }
}
