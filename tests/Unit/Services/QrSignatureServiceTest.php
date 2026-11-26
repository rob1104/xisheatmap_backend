<?php

namespace Tests\Unit\Services;

use App\Models\Ciudadano;
use App\Services\QrSignatureService;
use Tests\TestCase;

class QrSignatureServiceTest extends TestCase
{
    public function test_generates_ed25519_keypair_signs_and_verifies_canonical_payload(): void
    {
        $service = new QrSignatureService();
        $keys = $service->generateKeyPair();

        $this->assertSame(32, strlen(base64_decode($keys['private_key'], true)));
        $this->assertSame(32, strlen(base64_decode($keys['public_key'], true)));

        $ciudadano = new Ciudadano([
            'clave_unica' => 'HMX-CID-000123',
            'qr_version' => 2,
        ]);

        $payload = $service->buildPayload($ciudadano, $keys['private_key']);

        $this->assertSame(['id', 'v', 'sig'], array_keys($payload));
        $this->assertSame('HMX-CID-000123', $payload['id']);
        $this->assertSame(2, $payload['v']);
        $this->assertNotEmpty($payload['sig']);

        // Verifica correctamente con la llave pública correspondiente
        $this->assertTrue($service->verify($payload['id'], $payload['v'], $payload['sig'], $keys['public_key']));

        // Rechaza manipulaciones de versión, clave o firma
        $this->assertFalse($service->verify($payload['id'], 1, $payload['sig'], $keys['public_key']));
        $this->assertFalse($service->verify('HMX-CID-999999', $payload['v'], $payload['sig'], $keys['public_key']));
        $this->assertFalse($service->verify($payload['id'], $payload['v'], $payload['sig'] . 'X', $keys['public_key']));

        // Rechaza verificación con otra llave pública
        $otherKeys = $service->generateKeyPair();
        $this->assertFalse($service->verify($payload['id'], $payload['v'], $payload['sig'], $otherKeys['public_key']));
    }
}
