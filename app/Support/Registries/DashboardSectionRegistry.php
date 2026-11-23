<?php

namespace App\Support\Registries;

/**
 * Sections on the account page. Modules register sections here instead of the
 * account module reading their data directly.
 */
final class DashboardSectionRegistry
{
    /** @var array<string, DashboardSection> */
    private array $sections = [];

    public function register(DashboardSection $section): void
    {
        $this->sections[$section->key] = $section;
    }

    /**
     * @return list<DashboardSection>
     */
    public function all(): array
    {
        $sections = array_values($this->sections);
        usort($sections, fn (DashboardSection $a, DashboardSection $b) => $a->order <=> $b->order);

        return $sections;
    }
}
