<?php

namespace App\Models;

use Illuminate\Database\Eloquent\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ListaNominalCorte extends Model
{
    use HasFactory, LogsActivity;

    protected $fillables = [
        "fecha_corte",
        "fuente",
        "descripcion",
        "is_active",
    ];

    protected function casts(): array
    {
        return [
            "fecha_corte" => "date",
            "is_active" => "boolean"
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(ListaNominalCorte::class, "lista_nominal_corte_id");
    }

    public function scopeActive($query)
    {
        return $query->where("is_active", true);
    }

    public function scopeLatestFirts($query)
    {
        return $query->orderBy("fecha_corte", "desc");
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Corte de Lista Nominal
            {$eventName}");
    }
}
