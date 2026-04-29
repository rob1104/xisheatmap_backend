<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TarjetaDescuento extends Model
{
    use LogsActivity;

    protected $table = 'tarjetas_descuento';

    protected $fillable = [
        'folio', 'ine_record_id', 'user_id', 'curp', 'email_envio',
        'qr_data_json', 'image_path', 'enviado_por_correo', 'fecha_validez_fin'
    ];

    protected $casts = [
        'qr_data_json' => 'array', // Laravel convierte el JSON a array automáticamente
        'fecha_validez_fin' => 'datetime',
        'enviado_por_correo' => 'boolean',
    ];

    // Relaciones
    public function ineRecord() { return $this->belongsTo(IneRecord::class); }
    public function brigadista() { return $this->belongsTo(User::class, 'user_id'); }

    // Accessor para formatear el folio automáticamente al consultarlo
    public function getFolioFormateadoAttribute()
    {
        // TD + Año actual + ID con ceros a la izquierda (ej: TD23-00045)
        return 'TD' . $this->created_at->format('y') . '-' . str_pad($this->id, 5, '0', STR_PAD_LEFT);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            // Guarda los cambios de TODOS los campos fillable
            ->logFillable()
            // Solo guarda el registro si realmente hubo un cambio (ignora si le dan guardar sin modificar nada)
            ->logOnlyDirty()
            // Le da un nombre descriptivo a la acción
            ->setDescriptionForEvent(fn(string $eventName) => "Tarjetas de descuento {$eventName}");
    }
}
