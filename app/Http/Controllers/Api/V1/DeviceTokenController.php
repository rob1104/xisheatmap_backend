<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreDeviceTokenRequest;
use App\Models\UserDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    public function store(StoreDeviceTokenRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();
        $tokenHash = UserDevice::hashToken($validated['fcm_token']);
        $ahora = now();

        // Si el mismo token FCM existía en otro registro/usuario, se desactiva para evitar envíos cruzados
        UserDevice::query()
            ->where('fcm_token_hash', $tokenHash)
            ->where(function ($q) use ($user, $validated): void {
                $q->where('user_id', '!=', $user->id)
                    ->orWhere('dispositivo_id', '!=', $validated['dispositivo_id']);
            })
            ->delete();

        $device = UserDevice::updateOrCreate(
            [
                'user_id' => $user->id,
                'dispositivo_id' => $validated['dispositivo_id'],
            ],
            [
                'fcm_token' => $validated['fcm_token'],
                'fcm_token_hash' => $tokenHash,
                'plataforma' => $validated['plataforma'] ?? 'ANDROID',
                'activo' => true,
                'last_seen_at' => $ahora,
                'revoked_at' => null,
            ]
        );

        return response()->json([
            'message' => 'Dispositivo registrado correctamente.',
            'data' => [
                'id' => $device->id,
                'dispositivo_id' => $device->dispositivo_id,
                'plataforma' => $device->plataforma,
                'activo' => $device->activo,
                'last_seen_at' => $device->last_seen_at?->copy()->utc()->toIso8601String(),
            ],
        ], 200);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'dispositivo_id' => ['required', 'string', 'max:100'],
        ]);

        UserDevice::query()
            ->where('user_id', $request->user()->id)
            ->where('dispositivo_id', $validated['dispositivo_id'])
            ->update([
                'activo' => false,
                'revoked_at' => now(),
            ]);

        return response()->json([
            'message' => 'Token de dispositivo revocado correctamente.',
        ], 200);
    }
}
