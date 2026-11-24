<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecuenciaFolio extends Model
{
    protected $table = 'secuencias_folios';

    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = [
        'jornada_id',
        'seccion_electoral_id',
        'ultimo_consecutivo',
    ];

    protected function casts(): array
    {
        return [
            'ultimo_consecutivo' => 'integer',
        ];
    }

    public function jornada(): BelongsTo
    {
        return $this->belongsTo(Jornada::class, 'jornada_id');
    }

    public function seccionElectoral(): BelongsTo
    {
        return $this->belongsTo(SeccionElectoral::class, 'seccion_electoral_id');
    }
}
