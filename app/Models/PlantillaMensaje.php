<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlantillaMensaje extends Model
{
    use HasFactory;

    protected $table = 'plantillas_mensajes';

    protected $fillable = [
        'codigo',
        'canal',
        'cuerpo_plantilla',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function notificaciones(): HasMany
    {
        return $this->hasMany(NotificacionLog::class, 'mensaje_plantilla_id');
    }

    public function scopeActive($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Renderiza la plantilla institucional reemplazando únicamente placeholders {{clave}}.
     *
     * @param  array<string, scalar|null>  $variables
     */
    public function render(array $variables): string
    {
        $output = $this->cuerpo_plantilla;

        foreach ($variables as $key => $value) {
            $output = str_replace('{{' . $key . '}}', (string) ($value ?? ''), $output);
        }

        return $output;
    }
}
