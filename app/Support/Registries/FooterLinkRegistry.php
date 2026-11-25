<?php

namespace App\Support\Registries;

final class FooterLinkRegistry
{
    /** @var array<string, FooterLink> */
    private array $links = [];

    public function register(FooterLink $link): void
    {
        $this->links[$link->key] = $link;
    }

    /**
     * Visible links: label => URL.
     *
     * @return array<string, string>
     */
    public function visible(): array
    {
        $links = array_values($this->links);
        usort($links, fn (FooterLink $a, FooterLink $b) => $a->order <=> $b->order);

        $visible = [];
        foreach ($links as $link) {
            $url = ($link->url)();
            if ($url !== null) {
                $visible[$link->label] = $url;
            }
        }

        return $visible;
    }
}
