<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Apoyo extends Model
{
    use HasFactory;

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
}
