<?php

namespace App\Console\Commands;

use App\Services\QrSignatureService;
use Illuminate\Console\Command;

class GenerateParticipacionQrKeysCommand extends Command
{
    protected $signature = 'participacion:generate-qr-keys';

    protected $description = 'Genera un par de llaves asimétricas Ed25519 (base64) para firma y verificación offline de códigos QR.';

    public function handle(QrSignatureService $qrSignatureService): int
    {
        $keys = $qrSignatureService->generateKeyPair();

        $this->info('Par de llaves Ed25519 generado exitosamente.');
        $this->newLine();
        $this->line('Agregar al archivo .env del backend (NUNCA exponer la llave privada en el cliente):');
        $this->line('PARTICIPACION_QR_PRIVATE_KEY=' . $keys['private_key']);
        $this->line('PARTICIPACION_QR_PUBLIC_KEY=' . $keys['public_key']);
        $this->newLine();
        $this->line('Embeber únicamente PARTICIPACION_QR_PUBLIC_KEY en xisHeatMap_app (@noble/ed25519).');

        return self::SUCCESS;
    }
}
