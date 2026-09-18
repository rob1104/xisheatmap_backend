<?php

namespace App\Console\Commands;

use App\Contracts\SpatialServiceInterface;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

class AssingSeccionToApoyosCommand extends Command
{
    protected  $signature = 'spatial:assign-apoyos {--bulk : Ejecutar mediante sentencia SQL
                                directa}';

    protected $description = 'Auto-asigna la sección electoral correspondiente a los apoyos
                                mediante Point-in-Polygon';

    public function handle(SpatialServiceInterface $spatial): int
    {
        $this->info('Iniciando auto-asignacion territorial de apoyos...');

        $res = $spatial->assignSeccionToApoyos($this->option('bulk'));

        $this->table(['metrica','valor'], [
            ['modo', $res['modo']],
            ['Registros asignados', $res['asignados']],
            ['Sin cobertura', $res['sin_cobertura'] ?? 'N/A'],
        ]);

        $this->info('¡Proceso finalizado!');
        return Command::SUCCESS;
    }
}
