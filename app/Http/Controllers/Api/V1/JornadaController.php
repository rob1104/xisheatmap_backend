<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Jornada;
use App\Models\TipoApoyo;
use Illuminate\Http\JsonResponse;

class JornadaController extends Controller
{
    public function activa(): JsonResponse
    {
        $ahora = now();

        $jornada = Jornada::query()
            ->active()
            ->where('fecha_inicio', '<=', $ahora)
            ->where('fecha_fin', '>=', $ahora)
            ->orderByDesc('fecha_inicio')
            ->first();

        $tiposApoyo = TipoApoyo::query()
            ->active()
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'descripcion']);

        return response()->json([
            'server_time' => $ahora->copy()->utc()->toIso8601String(),
            'jornada' => $jornada ? [
                'id' => $jornada->id,
                'nombre' => $jornada->nombre,
                'tipo_jornada' => $jornada->tipo_jornada,
                'fecha_inicio' => $jornada->fecha_inicio->copy()->utc()->toIso8601String(),
                'fecha_fin' => $jornada->fecha_fin->copy()->utc()->toIso8601String(),
                'aviso_privacidad_version' => $jornada->aviso_privacidad_version,
            ] : null,
            'tipos_apoyo' => $tiposApoyo,
        ]);
    }
}
