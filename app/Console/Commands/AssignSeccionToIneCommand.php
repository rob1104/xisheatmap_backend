<?php

namespace App\Console\Commands;

use App\Contracts\SpatialServiceInterface;
use Illuminate\Console\Command;

class AssignSeccionToIneCommand extends Command
{
    protected $signature = 'spatial:assign-ine {--bulk : Ejecutar mediante sentencia SQL directa} {--force : Forzar sincronización de registros que ya tienen sección}';

    protected $description = 'Auto-asigna o sincroniza la sección electoral de simpatizantes INE según sus coordenadas GPS reales';

    public function handle(SpatialServiceInterface $spatial): int
    {
        $this->info('Iniciando sincronización territorial de simpatizantes INE...');

        $force = (bool) $this->option('force');
        $res = $spatial->assignSeccionToIneRecords($this->option('bulk'), $force);

        $this->table(['Métrica', 'Valor'], [
            ['Modo', $res['modo']],
            ['Total procesados', $res['total_procesados'] ?? 'N/A'],
            ['Registros actualizados/asignados', $res['asignados']],
            ['Registros sin cambios', $res['sin_cambios'] ?? 'N/A'],
            ['Sin cobertura', $res['sin_cobertura'] ?? 'N/A'],
        ]);

        $this->info('¡Proceso de sincronización finalizado con éxito!');
        return Command::SUCCESS;
    }
}
