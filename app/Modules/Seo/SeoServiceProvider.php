<?php

namespace App\Modules\Seo;

use App\Modules\Seo\Models\SeoSetting;
use App\Support\Registries\HeadContribution;
use App\Support\Registries\PageContext;
use App\Support\Registries\PageHeadRegistry;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class SeoServiceProvider extends ServiceProvider
{
    public function boot(PageHeadRegistry $head): void
    {
        $this->loadRoutesFrom(__DIR__.'/routes.php');

        RateLimiter::for('seo', fn (Request $request) => Limit::perMinute(60)->by('seo:'.$request->ip()));

        // Title, description, canonical, Open Graph and Schema.org for every public page.
        $head->register(new HeadContribution(
            key: 'seo',
            view: 'seo.head',
            data: function (PageContext $page) {
                $settings = SeoSetting::current();
                $isHome = $page->routeName === 'home';

                return [
                    'page' => $page,
                    'settings' => $settings,
                    'isHome' => $isHome,
                    'title' => $isHome ? $settings->homeTitle() : (($page->title !== null ? $page->title.' · ' : '').$settings->siteName()),
                    'description' => $isHome ? $settings->homeDescription() : $settings->defaultDescription(),
                    'robots' => $settings->indexing_enabled ? $page->robots : 'noindex,nofollow',
                ];
            },
            order: 10,
        ));
    }
}
