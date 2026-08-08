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
        'apoyo',
        'estatus_de_apoyo',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Apoyo {$eventName}");
    }
}
