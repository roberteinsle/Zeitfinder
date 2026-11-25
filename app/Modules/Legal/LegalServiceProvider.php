<?php

namespace App\Modules\Legal;

use App\Modules\Legal\Models\LegalSetting;
use App\Support\Registries\FooterLink;
use App\Support\Registries\FooterLinkRegistry;
use App\Support\Registries\SitemapEntry;
use App\Support\Registries\SitemapRegistry;
use Illuminate\Support\ServiceProvider;

class LegalServiceProvider extends ServiceProvider
{
    public function boot(FooterLinkRegistry $footer, SitemapRegistry $sitemap): void
    {
        $this->loadRoutesFrom(__DIR__.'/routes.php');

        $footer->register(new FooterLink('imprint', __('Impressum'), fn () => LegalSetting::current()->hasOperator() ? route('imprint') : null, 10));
        $footer->register(new FooterLink('privacy', __('Datenschutz'), fn () => route('privacy'), 20));

        // The imprint carries a postal address and is therefore not offered to search engines.
        $sitemap->register('legal', fn () => [
            new SitemapEntry(route('privacy'), __('Datenschutz'), __('Welche Daten Zeitfinder speichert und warum.'), 'yearly', 0.3),
        ]);
    }
}
