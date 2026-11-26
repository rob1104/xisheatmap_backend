<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class NotificacionLog extends Model
{
    use HasFactory;

    protected $table = 'notificaciones_log';

    protected $fillable = [
        'referencia_type',
        'referencia_id',
        'destinatario_user_id',
        'tipo_destinatario',
        'canal',
        'mensaje_plantilla_id',
        'estado_envio',
        'intentos',
        'provider_message_id',
        'error_detalle',
        'fecha_envio',
    ];

    protected function casts(): array
    {
        return [
            'intentos' => 'integer',
            'fecha_envio' => 'datetime',
        ];
    }

    public function referencia(): MorphTo
    {
        return $this->morphTo();
    }

    public function destinatario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'destinatario_user_id');
    }

    public function plantilla(): BelongsTo
    {
        return $this->belongsTo(PlantillaMensaje::class, 'mensaje_plantilla_id');
    }
}
