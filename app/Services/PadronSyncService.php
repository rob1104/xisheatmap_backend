<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Ciudadano;
use App\Models\User;
use Carbon\Carbon;

class PadronSyncService
{
    /**
     * Sirve la delta paginada por cursor de ciudadanos dentro de las secciones asignadas
     * al operador. Incluye registros inactivos (activo = false) como tombstones para purga local.
     *
     * @return array<string, mixed>
     */
    public function syncForUser(User $user, ?string $since = null, ?string $cursor = null, ?int $perPage = null): array
    {
        $limit = max(1, min($perPage ?? (int) config('participacion.padron_sync_per_page', 200), 500));
        $serverTime = now('UTC');

        $query = Ciudadano::query()
            ->select([
                'id',
                'clave_unica',
                'nombre',
                'apellido_paterno',
                'apellido_materno',
                'telefono',
                'seccion_electoral_id',
                'qr_version',
                'activo',
                'updated_at',
            ])
            ->with('seccionElectoral:id,seccion');

        if (! $user->hasRole(UserRole::ADMINISTRADOR->value)) {
            $seccionIds = $user->assignedSeccionIds();
            if ($seccionIds === []) {
                return [
                    'data' => [],
                    'meta' => [
                        'server_time' => $serverTime->toIso8601String(),
                        'next_cursor' => null,
                        'has_more' => false,
                        'per_page' => $limit,
                    ],
                ];
            }

            $query->whereIn('seccion_electoral_id', $seccionIds);
        }

        if ($since !== null && trim($since) !== '') {
            $sinceAppTz = Carbon::parse($since)->setTimezone(config('app.timezone'));
            $query->where('updated_at', '>=', $sinceAppTz);
        }

        if ($cursor !== null && trim($cursor) !== '') {
            $decoded = $this->decodeCursor($cursor);
            if ($decoded !== null) {
                [$cursorUpdatedAt, $cursorId] = $decoded;
                $query->where(function ($q) use ($cursorUpdatedAt, $cursorId): void {
                    $q->where('updated_at', '>', $cursorUpdatedAt)
                        ->orWhere(function ($sub) use ($cursorUpdatedAt, $cursorId): void {
                            $sub->where('updated_at', '=', $cursorUpdatedAt)
                                ->where('id', '>', $cursorId);
                        });
                });
            }
        }

        $items = $query
            ->orderBy('updated_at')
            ->orderBy('id')
            ->limit($limit + 1)
            ->get();

        $hasMore = $items->count() > $limit;
        if ($hasMore) {
            $items = $items->slice(0, $limit)->values();
        }

        $nextCursor = null;
        if ($hasMore && $items->isNotEmpty()) {
            $last = $items->last();
            $nextCursor = $this->encodeCursor($last->updated_at->copy(), (int) $last->id);
        }

        return [
            'data' => $items->map(fn (Ciudadano $c) => [
                'id' => $c->id,
                'clave_unica' => $c->clave_unica,
                'nombre' => $c->nombre,
                'apellido_paterno' => $c->apellido_paterno,
                'apellido_materno' => $c->apellido_materno,
                'telefono' => $c->telefono,
                'seccion_electoral_id' => $c->seccion_electoral_id,
                'seccion' => $c->seccionElectoral?->seccion,
                'qr_version' => $c->qr_version,
                'activo' => $c->activo,
                'updated_at' => $c->updated_at?->copy()->utc()->toIso8601String(),
            ])->values()->all(),
            'meta' => [
                'server_time' => $serverTime->toIso8601String(),
                'next_cursor' => $nextCursor,
                'has_more' => $hasMore,
                'per_page' => $limit,
            ],
        ];
    }

    private function encodeCursor(Carbon $updatedAt, int $id): string
    {
        return rtrim(strtr(base64_encode($updatedAt->format('Y-m-d H:i:s.u') . '|' . $id), '+/', '-_'), '=');
    }

    /**
     * @return array{0: string, 1: int}|null
     */
    private function decodeCursor(string $cursor): ?array
    {
        $padded = strtr($cursor, '-_', '+/');
        $mod = strlen($padded) % 4;
        if ($mod > 0) {
            $padded .= str_repeat('=', 4 - $mod);
        }

        $raw = base64_decode($padded, true);
        if ($raw === false || ! str_contains($raw, '|')) {
            return null;
        }

        [$timestamp, $id] = explode('|', $raw, 2);
        if (! is_numeric($id)) {
            return null;
        }

        return [$timestamp, (int) $id];
    }
}
