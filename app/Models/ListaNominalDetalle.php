<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListaNominalDetalle extends Model
{
    use HasFactory;

    protected $table = "lista_nominal_detalles";

    protected $fillable = [
        "lista_nominal_corte_id",
        "seccion_electoral_id",
        "total_lista_nominal",
        "padron_electoral",
        "hombres",
        "mujeres",
        "no_binario",
    ];

    protected function casts(): array
    {
        return [
            'total_lista_nominal' => 'integer',
            'padron_electoral'    => 'integer',
            'hombres'             => 'integer',
            'mujeres'             => 'integer',
            'no_binario'          => 'integer',
        ];
    }

    public function corte(): BelongsTo
    {
        return $this->BelongsTo(ListaNominalCorte::class, "lista_nominal_corte_id");
    }

    public function seccionElectoral(): BelongsTo
    {
        return $this->belongsTo(SeccionElectoral::class, "seccion_electoral_id");
    }
}
