<?php

namespace App\Modules\Polls;

use App\Models\User;
use App\Modules\Polls\Actions\PruneOldPolls;
use App\Modules\Polls\Enums\PollStatus;
use App\Modules\Polls\Models\Participant;
use App\Modules\Polls\Models\Poll;
use App\Support\Registries\AdminStat;
use App\Support\Registries\AdminStatRegistry;
use App\Support\Registries\DashboardSection;
use App\Support\Registries\DashboardSectionRegistry;
use App\Support\Registries\MaintenanceRegistry;
use App\Support\Registries\SitemapEntry;
use App\Support\Registries\SitemapRegistry;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class PollsServiceProvider extends ServiceProvider
{
    public function boot(DashboardSectionRegistry $dashboard, SitemapRegistry $sitemap, MaintenanceRegistry $maintenance, AdminStatRegistry $stats): void
    {
        $this->loadRoutesFrom(__DIR__.'/routes.php');

        RateLimiter::for('poll-create', fn (Request $request) => [
            Limit::perHour(20)->by('poll-create-ip:'.$request->ip()),
            Limit::perDay(100)->by('poll-create-user:'.($request->user()?->getAuthIdentifier() ?? $request->ip())),
        ]);
        RateLimiter::for('vote', fn (Request $request) => Limit::perMinute(20)->by('vote:'.$request->ip()));
        RateLimiter::for('secret-links', fn (Request $request) => Limit::perMinute(20)->by('secret-links:'.$request->ip()));
        RateLimiter::for('poll-manage', fn (Request $request) => Limit::perMinute(60)->by('poll-manage:'.$request->ip()));

        $recent = fn () => now()->subDays(30);
        $stats->register(new AdminStat('polls.open', __('Offene Umfragen'),
            fn () => Poll::query()->where('status', PollStatus::Open)->count(),
            fn () => __('von :count insgesamt', ['count' => Poll::query()->count()]),
            order: 10));
        $stats->register(new AdminStat('polls.new', __('Neue Umfragen'),
            fn () => Poll::query()->where('created_at', '>=', $recent())->count(),
            fn () => __('in den letzten 30 Tagen'), order: 20));
        $stats->register(new AdminStat('polls.votes', __('Teilnahmen'),
            fn () => Participant::query()->where('created_at', '>=', $recent())->count(),
            fn () => __('in den letzten 30 Tagen'), order: 30));
        $stats->register(new AdminStat('polls.finalized', __('Festgelegte Termine'),
            fn () => Poll::query()->where('status', PollStatus::Finalized)->where('finalized_at', '>=', $recent())->count(),
            fn () => __('in den letzten 30 Tagen'), order: 40));

        $maintenance->register('polls', fn () => app(PruneOldPolls::class)());

        // Only the create page is public; polls themselves are never indexed.
        $sitemap->register('polls', fn () => [
            new SitemapEntry(route('polls.create'), __('Neue Umfrage'), __('Termine vorschlagen und eine Terminumfrage ohne Konto anlegen.'), 'monthly', 0.8),
        ]);

        // The account page shows the user's polls through the dashboard registry.
        $dashboard->register(new DashboardSection(
            key: 'polls',
            view: 'polls.dashboard-section',
            data: fn (User $user) => [
                'ownPolls' => Poll::query()->where('user_id', $user->id)->withCount('participants')->latest()->limit(50)->get(),
                'participations' => Participant::query()
                    ->where(fn ($q) => $q->where('user_id', $user->id)->orWhere('email', mb_strtolower($user->email)))
                    ->with('poll')->latest()->limit(50)->get()
                    ->filter(fn (Participant $p) => $p->poll->user_id !== $user->id),
            ],
            order: 10,
        ));
    }
}
