<?php

namespace Database\Seeders;

use App\Enums\ParticipacionPermission;
use App\Enums\UserRole;
use App\Jobs\SendSolicitudLogisticaFcmJob;
use App\Models\PlantillaMensaje;
use App\Models\TipoApoyo;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ParticipacionPermissionSeeder extends Seeder
{
    public function run(): void
    {
        if (! User::hasSpatiePermissionTables()) {
            throw new \RuntimeException('Las tablas de Spatie Permission no existen. Ejecuta las migraciones antes del seeder.');
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (ParticipacionPermission::cases() as $permission) {
            Permission::firstOrCreate([
                'name' => $permission->value,
                'guard_name' => 'web',
            ]);
        }

        $modulePermissions = ParticipacionPermission::values();

        $rolePermissions = [
            UserRole::ADMINISTRADOR->value => $modulePermissions,
            UserRole::COORDINADOR_TERRITORIAL->value => [
                ParticipacionPermission::VER->value,
                ParticipacionPermission::ATENDER_SOLICITUDES->value,
                ParticipacionPermission::EXPORTAR->value,
            ],
            UserRole::GESTOR_SECCIONAL->value => [
                ParticipacionPermission::VER->value,
                ParticipacionPermission::ATENDER_SOLICITUDES->value,
            ],
            UserRole::PRESIDENTE_COMITE->value => [],
            UserRole::INTEGRANTE_COMITE->value => [],
            UserRole::AFINES->value => [],
        ];

        foreach ($rolePermissions as $roleName => $permissionsToGive) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            $permissionsToRevoke = array_diff($modulePermissions, $permissionsToGive);

            if ($permissionsToGive !== []) {
                $role->givePermissionTo($permissionsToGive);
            }

            if ($permissionsToRevoke !== []) {
                $role->revokePermissionTo($permissionsToRevoke);
            }
        }

        // Catálogo inicial cerrado de apoyos logísticos (sin referencias político-electorales)
        $tiposApoyo = [
            [
                'nombre' => 'Traslado por movilidad reducida',
                'descripcion' => 'Apoyo logístico de traslado para personas con movilidad reducida o adultos mayores.',
            ],
            [
                'nombre' => 'Hidratación',
                'descripcion' => 'Suministro de agua o hidratación en punto de atención.',
            ],
            [
                'nombre' => 'Orientación de casilla',
                'descripcion' => 'Orientación geográfica sobre ubicación de casilla correspondiente a la sección.',
            ],
            [
                'nombre' => 'Asistencia operativa general',
                'descripcion' => 'Atención logística general reportada por personal de campo.',
            ],
        ];

        foreach ($tiposApoyo as $tipo) {
            TipoApoyo::firstOrCreate(
                ['nombre' => $tipo['nombre']],
                ['descripcion' => $tipo['descripcion'], 'activo' => true]
            );
        }

        // Plantilla institucional FCM autorizada para solicitudes logísticas
        PlantillaMensaje::firstOrCreate(
            ['codigo' => SendSolicitudLogisticaFcmJob::PLANTILLA_CODIGO],
            [
                'canal' => 'FCM',
                'cuerpo_plantilla' => 'Nueva solicitud logística ({{tipo_apoyo}}) en sección {{seccion}}. Folio: {{folio}}.',
                'activo' => true,
            ]
        );

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
