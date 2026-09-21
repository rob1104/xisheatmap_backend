<?php

namespace App\Console\Commands;

use App\Contracts\SpatialServiceInterface;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

class AuditIneCaptureCommand extends Command
{
    protected $signature = 'spatial:audit-ine {--limit=500 : Límite de registros a auditar}';
    protected $description = 'Audita la consistencia entre el GPS de captura y la sección de credencial en IneRecords';

        public function handle(SpatialServiceInterface $spatial): int
        {
            $this->info('Auditando registros INE contra polígonos oficiales...');

            $res = $spatial->auditIneRecords((int)$this->option('limit'));

            $this->table(['Métrica', 'Resultado'], [
                ['Total Analizados', $res['total_analizados']],
                ['Coincidentes (GPS = Credencial)', $res['coincidentes']],
                ['Porcentaje de Consistencia', $res['porcentaje_consistencia'] . '%'],
                ['Discrepancias (Fuera de sección)', $res['total_discrepancias']],
                ['Fuera de Victoria / Polígonos', $res['total_fuera_cobertura']],
            ]);

            if (!empty($res['discrepancias'])) {
                $this->warn("\nPrimeras anomalías detectadas:");
                $this->table(
                    ['ID', 'Sec. Credencial', 'Sec. GPS Real', 'Distrito'],
                    collect($res['discrepancias'])->take(10)->map(fn($d) => [
                        $d['id'], $d['seccion_credencial'],
                        $d['seccion_gps_real'], $d['distrito_local']
                    ])->toArray()
                );
            }

            return Command::SUCCESS;
        }
}
