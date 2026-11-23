<?php

namespace App\Support;

/**
 * Only allows redirects to internal paths (prevents open redirects).
 */
final class SafeRedirect
{
    public static function internalPath(mixed $target, string $fallback = '/'): string
    {
        if (! is_string($target) || $target === '' || strlen($target) > 2000) {
            return $fallback;
        }

        // Must be a path on this host: starts with a single slash, no scheme, no backslashes.
        if (! str_starts_with($target, '/') || str_starts_with($target, '//') || str_contains($target, '\\')) {
            return $fallback;
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $target) === 1) {
            return $fallback;
        }

        return $target;
    }
}
