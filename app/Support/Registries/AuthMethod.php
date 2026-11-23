<?php

namespace App\Support\Registries;

/**
 * A sign-in method shown on the login page.
 */
final readonly class AuthMethod
{
    public function __construct(
        public string $key,
        public string $view,
        public int $order = 100,
    ) {}
}
