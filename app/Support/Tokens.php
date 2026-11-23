<?php

namespace App\Support;

/**
 * Secret URL tokens (magic links, admin links, edit links).
 *
 * Tokens are 32 random bytes as 64 hex characters. Only the SHA-256 hash is stored.
 */
final class Tokens
{
    public const PATTERN = '[0-9a-f]{64}';

    /**
     * @return array{0: string, 1: string} plain token and its hash
     */
    public static function generate(): array
    {
        $plain = bin2hex(random_bytes(32));

        return [$plain, self::hash($plain)];
    }

    public static function hash(string $plain): string
    {
        return hash('sha256', $plain);
    }

    public static function isWellFormed(mixed $token): bool
    {
        return is_string($token) && preg_match('/^'.self::PATTERN.'$/D', $token) === 1;
    }
}
