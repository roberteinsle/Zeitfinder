<?php

namespace App\Support\Registries;

/**
 * Sign-in methods offered on the login page, in display order.
 */
final class AuthMethodRegistry
{
    /** @var array<string, AuthMethod> */
    private array $methods = [];

    public function register(AuthMethod $method): void
    {
        $this->methods[$method->key] = $method;
    }

    /**
     * @return list<AuthMethod>
     */
    public function all(): array
    {
        $methods = array_values($this->methods);
        usort($methods, fn (AuthMethod $a, AuthMethod $b) => $a->order <=> $b->order);

        return $methods;
    }

    public function has(string $key): bool
    {
        return isset($this->methods[$key]);
    }
}
