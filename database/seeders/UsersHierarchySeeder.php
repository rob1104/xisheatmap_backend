<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class UsersHierarchySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('es_ES');
        $password = Hash::make('password123');

        // Borrar todos excepto ID 1 y 3
        User::whereNotIn('id', [1, 3])->delete();

        // Buscar el Administrador existente (ID 3)
        $admin = User::find(3);

        if (!$admin || $admin->role->value !== UserRole::ADMINISTRADOR->value) {
            $this->command->error('El usuario con ID 3 no es Administrador. Usaremos el primer Administrador disponible.');
            $admin = User::where('role', UserRole::ADMINISTRADOR->value)->first();
            if (!$admin) return;
        }

        // Distribución exacta para asegurar que todos tengan hijos
        // Nivel 1: 2 hijos por admin = 2
        // Nivel 2: 2 hijos por padre = 4
        // Nivel 3: 2 hijos por padre = 8
        // Nivel 4: 2 hijos por padre = 16
        // Nivel 5: 1 hijo por padre = 16

        $levelsConfig = [
            1 => ['role' => UserRole::COORDINADOR_SECTOR->value, 'children_per_parent' => 2],
            2 => ['role' => UserRole::GESTOR_SECCIONAL->value, 'children_per_parent' => 2],
            3 => ['role' => UserRole::PRESIDENTE_COMITE->value, 'children_per_parent' => 2],
            4 => ['role' => UserRole::INTEGRANTE_COMITE->value, 'children_per_parent' => 2],
            5 => ['role' => UserRole::DESDOBLE->value, 'children_per_parent' => 1],
        ];

        $parentsByLevel = [
            0 => collect([$admin])
        ];

        foreach ($levelsConfig as $level => $config) {
            $parentsByLevel[$level] = collect();
            $possibleParents = $parentsByLevel[$level - 1];

            foreach ($possibleParents as $parent) {
                for ($i = 0; $i < $config['children_per_parent']; $i++) {
                    $user = User::create([
                        'name' => $faker->name,
                        'email' => $faker->unique()->safeEmail,
                        'password' => $password,
                        'role' => $config['role'],
                        'parent_id' => $parent->id,
                    ]);

                    $parentsByLevel[$level]->push($user);
                }
            }
            
            $this->command->info("Se generaron " . $parentsByLevel[$level]->count() . " usuarios del nivel: {$config['role']}");
        }
        
        $this->command->info('Organigrama perfectamente balanceado generado exitosamente.');
    }
}
