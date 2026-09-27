<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ListaNominalCorte extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
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

    public function detalles(): HasMany
    {
        return $this->hasMany(ListaNominalDetalle::class, "lista_nominal_corte_id");
    }

    public function scopeActive($query)
    {
        return $query->where("is_active", true);
    }

    public function scopeLatestFirst($query)
    {
        return $query->orderBy("fecha_corte", "desc");
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Corte de Lista Nominal {$eventName}");
    }
}
