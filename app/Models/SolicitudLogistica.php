<?php

namespace App\Models;

use App\Enums\SolicitudEstatus;
use App\Models\Concerns\LogsRequestMetadata;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SolicitudLogistica extends Model
{
    use HasFactory, LogsActivity, LogsRequestMetadata;

    protected $table = 'solicitudes_logisticas';

    protected $fillable = [
        'interaccion_id',
        'seccion_electoral_id',
        'tipo_apoyo_id',
        'descripcion_operativa',
        'estatus',
        'atendido_por_id',
        'fecha_cierre',
    ];

    protected function casts(): array
    {
        return [
            'estatus' => SolicitudEstatus::class,
            'fecha_cierre' => 'datetime',
        ];
    }

    public function interaccion(): BelongsTo
    {
        return $this->belongsTo(InteraccionParticipacion::class, 'interaccion_id');
    }

    public function seccionElectoral(): BelongsTo
    {
        return $this->belongsTo(SeccionElectoral::class, 'seccion_electoral_id');
    }

    public function tipoApoyo(): BelongsTo
    {
        return $this->belongsTo(TipoApoyo::class, 'tipo_apoyo_id');
    }

    public function atendidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atendido_por_id');
    }

    public function notificaciones(): MorphMany
    {
        return $this->morphMany(NotificacionLog::class, 'referencia');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'interaccion_id',
                'seccion_electoral_id',
                'tipo_apoyo_id',
                'descripcion_operativa',
                'estatus',
                'atendido_por_id',
                'fecha_cierre',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Solicitud logística {$eventName}");
    }
}
