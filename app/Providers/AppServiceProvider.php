<?php

namespace App\Providers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Modules\Legal\Models\LegalSetting;
use App\Modules\Polls\Models\Poll;
use App\Modules\Seo\Models\SeoSetting;
use App\Policies\ActivityLogPolicy;
use App\Policies\LegalSettingPolicy;
use App\Policies\PollPolicy;
use App\Policies\SeoSettingPolicy;
use App\Policies\UserPolicy;
use App\Support\Maintenance;
use App\Support\Registries\AdminStatRegistry;
use App\Support\Registries\AuthMethodRegistry;
use App\Support\Registries\DashboardSectionRegistry;
use App\Support\Registries\DomainEventRegistry;
use App\Support\Registries\FooterLinkRegistry;
use App\Support\Registries\MaintenanceRegistry;
use App\Support\Registries\PageHeadRegistry;
use App\Support\Registries\SitemapEntry;
use App\Support\Registries\SitemapRegistry;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DomainEventRegistry::class);
        $this->app->singleton(AuthMethodRegistry::class);
        $this->app->singleton(DashboardSectionRegistry::class);
        $this->app->singleton(PageHeadRegistry::class);
        $this->app->singleton(SitemapRegistry::class);
        $this->app->singleton(FooterLinkRegistry::class);
        $this->app->singleton(MaintenanceRegistry::class);
        $this->app->singleton(AdminStatRegistry::class);
    }

    public function boot(SitemapRegistry $sitemap, MaintenanceRegistry $maintenance): void
    {
        // Links in mails are built from APP_URL only, never from the request's Host header.
        $appUrl = (string) config('app.url');
        URL::forceRootUrl($appUrl);
        if (str_starts_with($appUrl, 'https://')) {
            URL::forceScheme('https');
        }

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Poll::class, PollPolicy::class);
        Gate::policy(ActivityLog::class, ActivityLogPolicy::class);
        Gate::policy(SeoSetting::class, SeoSettingPolicy::class);
        Gate::policy(LegalSetting::class, LegalSettingPolicy::class);

        // Public pages of routes/web.php; modules register their own pages.
        $sitemap->register('pages', fn () => [
            new SitemapEntry(route('home'), __('Startseite'), __('Terminumfrage erstellen, Link teilen, gemeinsam den besten Termin finden.'), 'weekly', 1.0),
        ]);

        // Log entries contain names and IP addresses; they are not kept longer than polls.
        $maintenance->register('activity_logs', fn () => ActivityLog::query()
            ->where('created_at', '<', now()->subMonthsNoOverflow((int) config('zeitfinder.retention_months')))
            ->delete());
        Event::listen(DiagnosingHealth::class, fn () => app(Maintenance::class)->runIfDue());

        Carbon::setLocale('de');
        CarbonImmutable::setLocale('de');

        Password::defaults(fn () => Password::min(12));

        // Baseline for every web route; stricter limiters are defined by the modules.
        RateLimiter::for('global', fn (Request $request) => Limit::perMinute(300)->by('global:'.$request->ip()));

        // Vendor routes outside the web group (Livewire assets, Filament downloads) get the baseline limit too.
        $this->app->booted(function () {
            foreach (Route::getRoutes()->getRoutes() as $route) {
                $middleware = $route->middleware();
                $throttled = in_array('web', $middleware, true)
                    || array_filter($middleware, fn ($m) => str_contains($m, 'throttle')) !== [];
                if (! $throttled && $route->uri() !== 'up') {
                    $route->middleware('throttle:global');
                }
            }
        });
    }
}
