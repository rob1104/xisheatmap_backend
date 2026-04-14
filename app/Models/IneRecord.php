<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class IneRecord extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'user_id',
        'clave_elector',
        'curp',
        'ocr_numero',
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'fecha_nacimiento',
        'sexo',
        'calle_numero',
        'colonia',
        'codigo_postal',
        'municipio',
        'estado',
        'seccion',
        'vigencia',
        'latitud',
        'longitud',
        'foto_frente_path',
        'foto_reverso_path',
        'capturado_en',
    ];

    protected function casts(): array
    {
        return [
            // Fechas
            'fecha_nacimiento' => 'date',
            'capturado_en'     => 'datetime',

            // Coordenadas como números reales, no texto
            'latitud'          => 'decimal:8',
            'longitud'         => 'decimal:8',

        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            // Guarda los cambios de TODOS los campos fillable
            ->logFillable()
            // Solo guarda el registro si realmente hubo un cambio (ignora si le dan guardar sin modificar nada)
            ->logOnlyDirty()
            // Evita que guarde las rutas completas de las fotos en el log para ahorrar espacio
            ->dontLogIfAttributesChangedOnly(['foto_frente_path', 'foto_reverso_path'])
            // Le da un nombre descriptivo a la acción
            ->setDescriptionForEvent(fn(string $eventName) => "Expediente INE {$eventName}");
    }
}
