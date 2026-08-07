<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Apoyo;

class ApoyosSeeder extends Seeder
{
    public function run(): void
    {
        $json = file_get_contents(base_path('../apoyos_import.json'));
        $data = json_decode($json, true);
        
        $count = 0;
        foreach($data as $row) {
            Apoyo::create([
                'nombre' => $row['nombre'],
                'telefono' => $row['telefono'] == 'nan' ? null : $row['telefono'],
                'colonia' => $row['colonia'],
                'calle_y_numero' => $row['calle_y_numero'] == 'nan' ? 'S/N' : $row['calle_y_numero'],
                'latitud' => floatval($row['latitud']),
                'longitud' => floatval($row['longitud']),
                'apoyo' => $row['apoyo'],
                'estatus_de_apoyo' => $row['estatus_de_apoyo']
            ]);
            $count++;
        }
        
        echo "Importados {$count} registros de apoyos.\n";
    }
}
