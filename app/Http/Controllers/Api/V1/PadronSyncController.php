<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\PadronSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PadronSyncController extends Controller
{
    public function __construct(
        private readonly PadronSyncService $padronSyncService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'since' => ['nullable', 'date'],
            'cursor' => ['nullable', 'string', 'max:200'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        $payload = $this->padronSyncService->syncForUser(
            user: $request->user(),
            since: $validated['since'] ?? null,
            cursor: $validated['cursor'] ?? null,
            perPage: isset($validated['per_page']) ? (int) $validated['per_page'] : null
        );

        return response()->json($payload);
    }
}
