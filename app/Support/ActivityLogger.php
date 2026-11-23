<?php

namespace App\Support;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Writes changes to domain data into the activity log.
 *
 * Never pass secrets (tokens, passwords, TOTP secrets) in $properties; keys that look
 * like secrets are masked as a safety net.
 */
final class ActivityLogger
{
    private const SECRET_KEYS = ['token', 'password', 'secret', 'code', 'credential'];

    /**
     * @param  array<string, mixed>  $properties
     */
    public function log(string $action, ?Model $subject = null, array $properties = [], ?int $userId = null): void
    {
        ActivityLog::query()->create([
            'user_id' => $userId ?? Auth::id(),
            'action' => $action,
            'subject_type' => $subject !== null ? class_basename($subject) : null,
            'subject_id' => $subject?->getKey(),
            'properties' => $properties === [] ? null : $this->mask($properties),
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    private function mask(array $properties): array
    {
        foreach ($properties as $key => $value) {
            foreach (self::SECRET_KEYS as $needle) {
                if (str_contains(strtolower((string) $key), $needle)) {
                    $properties[$key] = '***';

                    continue 2;
                }
            }

            if (is_array($value)) {
                $properties[$key] = $this->mask($value);
            }
        }

        return $properties;
    }
}
