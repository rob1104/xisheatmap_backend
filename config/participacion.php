<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Llaves criptográficas Ed25519 para firma y verificación de códigos QR
    |--------------------------------------------------------------------------
    | Acepta llaves raw en base64 (32 bytes pública, 32 o 64 bytes privada)
    | o en formato PEM PKCS#8 / SubjectPublicKeyInfo.
    */
    'qr_private_key' => env('PARTICIPACION_QR_PRIVATE_KEY'),
    'qr_public_key' => env('PARTICIPACION_QR_PUBLIC_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Prefijo de folio interno (ej. HMX-2026-SEC1563-00001)
    |--------------------------------------------------------------------------
    */
    'folio_prefix' => env('PARTICIPACION_FOLIO_PREFIX', 'HMX'),

    /*
    |--------------------------------------------------------------------------
    | Cola dedicada para despacho de notificaciones (SLA < 5 s)
    |--------------------------------------------------------------------------
    */
    'notifications_queue' => env('PARTICIPACION_NOTIFICATIONS_QUEUE', 'notifications'),

    /*
    |--------------------------------------------------------------------------
    | Paginación de sincronización delta del padrón móvil
    |--------------------------------------------------------------------------
    */
    'padron_sync_per_page' => (int) env('PARTICIPACION_PADRON_SYNC_PER_PAGE', 200),
];
