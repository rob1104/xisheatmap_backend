<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\SolicitudEstatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSolicitudEstatusRequest extends FormRequest
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
            'estatus' => ['required', 'string', Rule::in(SolicitudEstatus::values())],
            'descripcion_operativa' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
