<?php

namespace App\Support\Registries;

/**
 * The only way modules react to each other's domain events.
 *
 * A module publishes event objects (e.g. App\Modules\Polls\Events\PollFinalized) through
 * dispatch(); other modules register listeners in their service provider. Modules never
 * call each other's internals directly.
 */
final class DomainEventRegistry
{
    /** @var array<class-string, list<callable(object): void>> */
    private array $listeners = [];

    /**
     * @template T of object
     *
     * @param  class-string<T>  $event
     * @param  callable(T): void  $listener
     */
    public function listen(string $event, callable $listener): void
    {
        $this->listeners[$event][] = $listener;
    }

    public function dispatch(object $event): void
    {
        foreach ($this->listeners[$event::class] ?? [] as $listener) {
            $listener($event);
        }
    }

    /**
     * @param  class-string  $event
     */
    public function hasListeners(string $event): bool
    {
        return ($this->listeners[$event] ?? []) !== [];
    }
}
