<?php

namespace App\Support\Registries;

/**
 * Head tags of the public layout (title, meta description, structured data …).
 * Modules register partials here instead of the layout reading their data.
 */
final class PageHeadRegistry
{
    /** @var array<string, HeadContribution> */
    private array $contributions = [];

    public function register(HeadContribution $contribution): void
    {
        $this->contributions[$contribution->key] = $contribution;
    }

    /**
     * @return list<HeadContribution>
     */
    public function all(): array
    {
        $contributions = array_values($this->contributions);
        usort($contributions, fn (HeadContribution $a, HeadContribution $b) => $a->order <=> $b->order);

        return $contributions;
    }
}
