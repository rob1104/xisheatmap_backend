<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\MetodoValidacion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreParticipacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'idempotency_uuid' => ['required', 'uuid'],
            'jornada_id' => ['required', 'integer', 'exists:jornadas,id'],
            'ciudadano_id' => ['required', 'integer', 'exists:ciudadanos,id'],
            'metodo_validacion' => ['required', 'string', Rule::in(MetodoValidacion::values())],
            'qr_version' => [
                Rule::requiredIf(fn () => $this->input('metodo_validacion') === MetodoValidacion::QR->value),
                'nullable',
                'integer',
                'min:1',
            ],
            'consentimiento_datos' => ['required', 'accepted'],
            'consentimiento_version' => ['required', 'string', 'max:20'],
            'fecha_captura_dispositivo' => ['nullable', 'date'],
            'latitud' => ['nullable', 'required_with:longitud', 'numeric', 'between:-90,90'],
            'longitud' => ['nullable', 'required_with:latitud', 'numeric', 'between:-180,180'],
            'solicitudes' => ['sometimes', 'array', 'max:10'],
            'solicitudes.*.tipo_apoyo_id' => [
                'required',
                'integer',
                Rule::exists('tipos_apoyo', 'id')->where('activo', true),
            ],
            'solicitudes.*.descripcion_operativa' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
