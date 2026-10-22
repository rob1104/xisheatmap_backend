<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Inicializar roles canónicos y permisos de Spatie
        $this->call([
            ListaNominalPermissionSeeder::class,
        ]);

        // Usuario inicial de prueba solo en entornos de desarrollo/pruebas (idempotente)
        if (app()->environment('local', 'testing')) {
            $admin = User::firstOrCreate(
                ['email' => 'test@example.com'],
                [
                    'name' => 'Test User',
                    'password' => bcrypt('password'),
                ]
            );

            $admin->syncRoles([UserRole::ADMINISTRADOR->value]);
        }

        // Carga de las 170 secciones electorales de Ciudad Victoria (INE)
        $this->call([
            SeccionesVictoriaSeeder::class,
        ]);

        // Carga de datos de prueba INE solo en entornos de desarrollo/pruebas si la tabla está vacía
        if (app()->environment('local', 'testing') && \App\Models\IneRecord::count() === 0) {
            $this->call([
                IneSeeder2::class,
            ]);
        }
    }
}
