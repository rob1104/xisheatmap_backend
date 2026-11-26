<?php

namespace App\Models;

use App\Enums\MetodoValidacion;
use App\Models\Concerns\LogsRequestMetadata;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class InteraccionParticipacion extends Model
{
    use HasFactory, LogsActivity, LogsRequestMetadata;

    protected $table = 'interacciones_participacion';

    protected $fillable = [
        'folio_interno',
        'idempotency_uuid',
        'jornada_id',
        'ciudadano_id',
        'user_id',
        'seccion_folio_id',
        'metodo_validacion',
        'qr_version_usada',
        'consentimiento_datos',
        'consentimiento_version',
        'latitud',
        'longitud',
        'seccion_gps',
        'fecha_captura_dispositivo',
        'fecha_servidor',
    ];

    protected function casts(): array
    {
        return [
            'metodo_validacion' => MetodoValidacion::class,
            'qr_version_usada' => 'integer',
            'consentimiento_datos' => 'boolean',
            'latitud' => 'decimal:8',
            'longitud' => 'decimal:8',
            'fecha_captura_dispositivo' => 'datetime',
            'fecha_servidor' => 'datetime',
        ];
    }

    public function jornada(): BelongsTo
    {
        return $this->belongsTo(Jornada::class, 'jornada_id');
    }

    public function ciudadano(): BelongsTo
    {
        return $this->belongsTo(Ciudadano::class, 'ciudadano_id');
    }

    public function operador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function seccionFolio(): BelongsTo
    {
        return $this->belongsTo(SeccionElectoral::class, 'seccion_folio_id');
    }

    public function solicitudesLogisticas(): HasMany
    {
        return $this->hasMany(SolicitudLogistica::class, 'interaccion_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'folio_interno',
                'idempotency_uuid',
                'jornada_id',
                'ciudadano_id',
                'user_id',
                'seccion_folio_id',
                'metodo_validacion',
                'qr_version_usada',
                'consentimiento_datos',
                'consentimiento_version',
                'seccion_gps',
                'fecha_servidor',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Interacción de participación {$eventName}");
    }
}
