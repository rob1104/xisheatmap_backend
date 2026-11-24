<?php

namespace App\Models\Concerns;

use Spatie\Activitylog\Contracts\Activity;

/**
 * Inyecta metadatos de red y dispositivo en las propiedades de Spatie Activitylog
 * (ip_origen y dispositivo_id proveniente del header X-Device-Id).
 */
trait LogsRequestMetadata
{
    public function tapActivity(Activity $activity, string $eventName): void
    {
        $request = app()->bound('request') ? request() : null;

        if (! $request) {
            return;
        }

        $properties = $activity->properties ?? collect();

        $activity->properties = $properties->merge([
            'ip_origen' => $request->ip(),
            'dispositivo_id' => $request->header('X-Device-Id'),
        ]);
    }
}
