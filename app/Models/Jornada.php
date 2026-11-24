<?php

namespace App\Models;

use App\Models\Concerns\LogsRequestMetadata;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Jornada extends Model
{
    use HasFactory, LogsActivity, LogsRequestMetadata;

    protected $table = 'jornadas';

    protected $fillable = [
        'nombre',
        'tipo_jornada',
        'fecha_inicio',
        'fecha_fin',
        'aviso_privacidad_version',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'datetime',
            'fecha_fin' => 'datetime',
            'activo' => 'boolean',
        ];
    }

    public function interacciones(): HasMany
    {
        return $this->hasMany(InteraccionParticipacion::class, 'jornada_id');
    }

    public function secuenciasFolios(): HasMany
    {
        return $this->hasMany(SecuenciaFolio::class, 'jornada_id');
    }

    public function scopeActive($query)
    {
        return $query->where('activo', true);
    }

    public function isVigenteEn(?CarbonInterface $momento = null): bool
    {
        if (! $this->activo) {
            return false;
        }

        $ahora = ($momento ?? now())->copy()->utc();

        return $ahora->betweenIncluded(
            $this->fecha_inicio->copy()->utc(),
            $this->fecha_fin->copy()->utc()
        );
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nombre', 'tipo_jornada', 'fecha_inicio', 'fecha_fin', 'aviso_privacidad_version', 'activo'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Jornada {$eventName}");
    }
}
