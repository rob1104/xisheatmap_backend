<?php

namespace App\Services;

use App\Models\Ciudadano;
use RuntimeException;

/**
 * Servicio criptográfico Ed25519 para emisión y verificación de códigos QR sin PII.
 *
 * Cadena canónica firmada: "v1|{clave_unica}|{qr_version}".
 * Soporta ext-sodium (preferido) y ext-openssl (OPENSSL_KEYTYPE_ED25519) de forma
 * totalmente interoperable con @noble/ed25519 en xisHeatMap_app (RFC 8032 / RFC 8410).
 */
class QrSignatureService
{
    private const CANONICAL_PREFIX = 'v1';

    /** Prefijo ASN.1 DER PKCS#8 v1 para semilla privada Ed25519 de 32 bytes (RFC 8410). */
    private const ED25519_PKCS8_PREFIX_HEX = '302e020100300506032b657004220420';

    /** Prefijo ASN.1 DER SubjectPublicKeyInfo para llave pública Ed25519 de 32 bytes (RFC 8410). */
    private const ED25519_SPKI_PREFIX_HEX = '302a300506032b6570032100';

    /**
     * Genera un nuevo par de llaves Ed25519 en base64 (32 bytes semilla privada + 32 bytes llave pública).
     *
     * @return array{private_key: string, public_key: string}
     */
    public function generateKeyPair(): array
    {
        if (extension_loaded('sodium')) {
            $keypair = sodium_crypto_sign_keypair();
            $secret64 = sodium_crypto_sign_secretkey($keypair);
            $seed32 = substr($secret64, 0, 32);
            $public32 = sodium_crypto_sign_publickey($keypair);

            return [
                'private_key' => base64_encode($seed32),
                'public_key' => base64_encode($public32),
            ];
        }

        if ($this->supportsOpenSslEd25519()) {
            $options = array_merge(
                $this->openSslConfigOptions(),
                ['private_key_type' => OPENSSL_KEYTYPE_ED25519]
            );

            $resource = openssl_pkey_new($options);
            if ($resource === false) {
                throw new RuntimeException('No fue posible generar llaves Ed25519 con OpenSSL: ' . (openssl_error_string() ?: 'error desconocido'));
            }

            $exported = openssl_pkey_export($resource, $privatePem, null, $this->openSslConfigOptions());
            $details = openssl_pkey_get_details($resource);

            if (! $exported || ! is_array($details) || empty($details['key'])) {
                throw new RuntimeException('No fue posible exportar el par de llaves Ed25519 desde OpenSSL.');
            }

            $seed32 = $this->extractRawPrivateSeedFromPem($privatePem);
            $public32 = $this->extractRawPublicKeyFromPem((string) $details['key']);

            return [
                'private_key' => base64_encode($seed32),
                'public_key' => base64_encode($public32),
            ];
        }

        throw new RuntimeException('El entorno PHP requiere ext-sodium o ext-openssl con soporte Ed25519.');
    }

    public function canonicalMessage(string $claveUnica, int $qrVersion): string
    {
        return self::CANONICAL_PREFIX . '|' . trim($claveUnica) . '|' . $qrVersion;
    }

    /**
     * Firma el mensaje canónico Ed25519 y retorna la firma de 64 bytes en Base64URL sin padding.
     */
    public function sign(string $claveUnica, int $qrVersion, ?string $privateKey = null): string
    {
        $keyInput = $privateKey ?? (string) config('participacion.qr_private_key', '');
        if ($keyInput === '') {
            throw new RuntimeException('PARTICIPACION_QR_PRIVATE_KEY no está configurada.');
        }

        $message = $this->canonicalMessage($claveUnica, $qrVersion);
        $rawPrivate = $this->decodePrivateKeyBytes($keyInput);

        if (extension_loaded('sodium')) {
            $secret64 = strlen($rawPrivate) === 64
                ? $rawPrivate
                : sodium_crypto_sign_secretkey(sodium_crypto_sign_seed_keypair($rawPrivate));

            $signature = sodium_crypto_sign_detached($message, $secret64);

            return $this->base64UrlEncode($signature);
        }

        if ($this->supportsOpenSslEd25519()) {
            $seed32 = substr($rawPrivate, 0, 32);
            $privatePem = $this->rawSeedToPkcs8Pem($seed32);
            $key = openssl_pkey_get_private($privatePem);
            if ($key === false) {
                throw new RuntimeException('La llave privada Ed25519 es inválida para OpenSSL.');
            }

            $signature = '';
            $ok = openssl_sign($message, $signature, $key, 0);
            if (! $ok || strlen($signature) !== 64) {
                throw new RuntimeException('Error al generar la firma Ed25519 con OpenSSL.');
            }

            return $this->base64UrlEncode($signature);
        }

        throw new RuntimeException('El entorno PHP requiere ext-sodium o ext-openssl con soporte Ed25519.');
    }

    /**
     * Verifica la firma Ed25519 Base64URL contra la cadena canónica del ciudadano.
     */
    public function verify(string $claveUnica, int $qrVersion, string $signatureBase64Url, ?string $publicKey = null): bool
    {
        $keyInput = $publicKey ?? (string) config('participacion.qr_public_key', '');
        if ($keyInput === '') {
            return false;
        }

        $signature = $this->base64UrlDecode($signatureBase64Url);
        if ($signature === null || strlen($signature) !== 64) {
            return false;
        }

        try {
            $public32 = $this->decodePublicKeyBytes($keyInput);
        } catch (RuntimeException) {
            return false;
        }

        $message = $this->canonicalMessage($claveUnica, $qrVersion);

        if (extension_loaded('sodium')) {
            return sodium_crypto_sign_verify_detached($signature, $message, $public32);
        }

        if ($this->supportsOpenSslEd25519()) {
            $publicPem = $this->rawPublicToSpkiPem($public32);
            $key = openssl_pkey_get_public($publicPem);
            if ($key === false) {
                return false;
            }

            return openssl_verify($message, $signature, $key, 0) === 1;
        }

        throw new RuntimeException('El entorno PHP requiere ext-sodium o ext-openssl con soporte Ed25519.');
    }

    /**
     * Construye el payload JSON mínimo sin PII listo para codificar en QR.
     *
     * @return array{id: string, v: int, sig: string}
     */
    public function buildPayload(Ciudadano $ciudadano, ?string $privateKey = null): array
    {
        $qrVersion = (int) $ciudadano->qr_version;

        return [
            'id' => $ciudadano->clave_unica,
            'v' => $qrVersion,
            'sig' => $this->sign($ciudadano->clave_unica, $qrVersion, $privateKey),
        ];
    }

    private function supportsOpenSslEd25519(): bool
    {
        return extension_loaded('openssl') && defined('OPENSSL_KEYTYPE_ED25519');
    }

    /**
     * En Windows, OpenSSL puede requerir la ruta explícita de openssl.cnf si OPENSSL_CONF no está definida.
     *
     * @return array<string, string>
     */
    private function openSslConfigOptions(): array
    {
        $envConf = getenv('OPENSSL_CONF');
        if (is_string($envConf) && $envConf !== '' && file_exists($envConf)) {
            return ['config' => $envConf];
        }

        $candidates = [
            dirname(PHP_BINARY) . DIRECTORY_SEPARATOR . 'extras' . DIRECTORY_SEPARATOR . 'ssl' . DIRECTORY_SEPARATOR . 'openssl.cnf',
            dirname(PHP_BINARY) . DIRECTORY_SEPARATOR . 'openssl.cnf',
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return ['config' => $path];
            }
        }

        return [];
    }

    private function decodePrivateKeyBytes(string $input): string
    {
        $trimmed = trim($input);

        if (str_contains($trimmed, 'BEGIN PRIVATE KEY')) {
            return $this->extractRawPrivateSeedFromPem($trimmed);
        }

        $decoded = base64_decode($trimmed, true);
        if ($decoded === false || ! in_array(strlen($decoded), [32, 64], true)) {
            throw new RuntimeException('La llave privada Ed25519 debe ser Base64 de 32/64 bytes o PEM PKCS#8.');
        }

        return $decoded;
    }

    private function decodePublicKeyBytes(string $input): string
    {
        $trimmed = trim($input);

        if (str_contains($trimmed, 'BEGIN PUBLIC KEY')) {
            return $this->extractRawPublicKeyFromPem($trimmed);
        }

        $decoded = base64_decode($trimmed, true);
        if ($decoded === false || strlen($decoded) !== 32) {
            throw new RuntimeException('La llave pública Ed25519 debe ser Base64 de 32 bytes o PEM SPKI.');
        }

        return $decoded;
    }

    private function extractRawPrivateSeedFromPem(string $pem): string
    {
        $der = $this->pemToDer($pem);
        $prefix = hex2bin(self::ED25519_PKCS8_PREFIX_HEX);

        if (strlen($der) === 48 && str_starts_with($der, $prefix)) {
            return substr($der, 16, 32);
        }

        // Fallback genérico: los últimos 32 bytes de PKCS#8 Ed25519 corresponden a la semilla
        if (strlen($der) >= 32) {
            return substr($der, -32);
        }

        throw new RuntimeException('Formato PEM PKCS#8 inválido para llave privada Ed25519.');
    }

    private function extractRawPublicKeyFromPem(string $pem): string
    {
        $der = $this->pemToDer($pem);
        $prefix = hex2bin(self::ED25519_SPKI_PREFIX_HEX);

        if (strlen($der) === 44 && str_starts_with($der, $prefix)) {
            return substr($der, 12, 32);
        }

        throw new RuntimeException('Formato PEM SPKI inválido para llave pública Ed25519.');
    }

    private function rawSeedToPkcs8Pem(string $seed32): string
    {
        $der = hex2bin(self::ED25519_PKCS8_PREFIX_HEX) . $seed32;

        return "-----BEGIN PRIVATE KEY-----\n"
            . chunk_split(base64_encode($der), 64, "\n")
            . "-----END PRIVATE KEY-----\n";
    }

    private function rawPublicToSpkiPem(string $public32): string
    {
        $der = hex2bin(self::ED25519_SPKI_PREFIX_HEX) . $public32;

        return "-----BEGIN PUBLIC KEY-----\n"
            . chunk_split(base64_encode($der), 64, "\n")
            . "-----END PUBLIC KEY-----\n";
    }

    private function pemToDer(string $pem): string
    {
        $body = preg_replace('/-----BEGIN [^-]+-----|-----END [^-]+-----|\s+/', '', $pem);
        $der = base64_decode((string) $body, true);

        if ($der === false) {
            throw new RuntimeException('No fue posible decodificar el bloque PEM.');
        }

        return $der;
    }

    private function base64UrlEncode(string $binary): string
    {
        return rtrim(strtr(base64_encode($binary), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $encoded): ?string
    {
        $padded = strtr(trim($encoded), '-_', '+/');
        $mod = strlen($padded) % 4;
        if ($mod > 0) {
            $padded .= str_repeat('=', 4 - $mod);
        }

        $decoded = base64_decode($padded, true);

        return $decoded === false ? null : $decoded;
    }
}
