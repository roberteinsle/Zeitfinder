<?php

namespace App\Modules\Polls;

use App\Models\User;
use App\Modules\Polls\Models\Participant;
use App\Modules\Polls\Models\Poll;
use App\Support\Registries\DashboardSection;
use App\Support\Registries\DashboardSectionRegistry;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class PollsServiceProvider extends ServiceProvider
{
    public function boot(DashboardSectionRegistry $dashboard): void
    {
        $this->loadRoutesFrom(__DIR__.'/routes.php');

        RateLimiter::for('poll-create', fn (Request $request) => [
            Limit::perHour(20)->by('poll-create-ip:'.$request->ip()),
            Limit::perDay(100)->by('poll-create-user:'.($request->user()?->getAuthIdentifier() ?? $request->ip())),
        ]);
        RateLimiter::for('vote', fn (Request $request) => Limit::perMinute(20)->by('vote:'.$request->ip()));
        RateLimiter::for('secret-links', fn (Request $request) => Limit::perMinute(20)->by('secret-links:'.$request->ip()));
        RateLimiter::for('poll-manage', fn (Request $request) => Limit::perMinute(60)->by('poll-manage:'.$request->ip()));

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
