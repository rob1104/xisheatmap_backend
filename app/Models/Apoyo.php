<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Apoyo extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'nombre',
        'telefono',
        'colonia',
        'calle_y_numero',
        'latitud',
        'longitud',
        'seccion',
        'apoyo',
        'estatus_de_apoyo',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->setDescriptionForEvent(function (string $eventName) { return "Apoyo {$eventName}"; });
    }

    public function seccionElectoral()
    {
        return $this->belongsTo(SeccionElectoral::class, 'seccion','seccion');
    }
}
