<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoApoyo extends Model
{
    use HasFactory;

    protected $table = 'tipos_apoyo';

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function solicitudesLogisticas(): HasMany
    {
        return $this->hasMany(SolicitudLogistica::class, 'tipo_apoyo_id');
    }

    public function scopeActive($query)
    {
        return $query->where('activo', true);
    }
}
