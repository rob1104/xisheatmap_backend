<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDeviceTokenRequest extends FormRequest
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
            'dispositivo_id' => ['required', 'string', 'max:100'],
            'fcm_token' => ['required', 'string', 'max:255'],
            'plataforma' => ['sometimes', 'string', Rule::in(['ANDROID', 'IOS', 'WEB'])],
        ];
    }
}
