<?php

namespace App\Models;

use App\Models\Concerns\LogsRequestMetadata;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Ciudadano extends Model
{
    use HasFactory, LogsActivity, LogsRequestMetadata;

    protected $table = 'ciudadanos';

    protected $fillable = [
        'ine_record_id',
        'clave_unica',
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'telefono',
        'seccion_electoral_id',
        'qr_version',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'qr_version' => 'integer',
            'activo' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Ciudadano $ciudadano): void {
            if (empty($ciudadano->clave_unica)) {
                $ciudadano->clave_unica = (string) Str::uuid();
            }

            if ($ciudadano->qr_version === null) {
                $ciudadano->qr_version = 1;
            }
        });
    }

    public function seccionElectoral(): BelongsTo
    {
        return $this->belongsTo(SeccionElectoral::class, 'seccion_electoral_id');
    }

    public function ineRecord(): BelongsTo
    {
        return $this->belongsTo(IneRecord::class, 'ine_record_id');
    }

    public function interacciones(): HasMany
    {
        return $this->hasMany(InteraccionParticipacion::class, 'ciudadano_id');
    }

    public function revocarQr(): self
    {
        $this->qr_version = ((int) $this->qr_version) + 1;
        $this->save();

        return $this;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'ine_record_id',
                'clave_unica',
                'nombre',
                'apellido_paterno',
                'apellido_materno',
                'telefono',
                'seccion_electoral_id',
                'qr_version',
                'activo',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Ciudadano {$eventName}");
    }
}
