<?php

namespace App\Modules\Accounts;

use App\Models\User;
use App\Modules\Accounts\Console\ResetPasskeysCommand;
use App\Modules\Accounts\Http\Middleware\EnsurePasskeySession;
use App\Modules\Accounts\Models\MagicLink;
use App\Modules\Accounts\Support\AdminBootstrap;
use App\Support\EmailAddress;
use App\Support\Registries\AuthMethod;
use App\Support\Registries\AuthMethodRegistry;
use App\Support\Registries\MaintenanceRegistry;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\Events\PasskeyVerified;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Passkeys;

class AccountsServiceProvider extends ServiceProvider
{
    public function boot(AuthMethodRegistry $authMethods, MaintenanceRegistry $maintenance): void
    {
        $this->loadRoutesFrom(__DIR__.'/routes.php');

        if ($this->app->runningInConsole()) {
            $this->commands([ResetPasskeysCommand::class]);
        }

        $authMethods->register(new AuthMethod('magic_link', 'accounts.methods.magic-link', 10));
        $authMethods->register(new AuthMethod('passkey', 'accounts.methods.passkey', 20));
        $authMethods->register(new AuthMethod('password', 'accounts.methods.password', 30));

        // Used or expired links are worthless but still contain the address.
        $maintenance->register('magic_links', fn () => MagicLink::query()->where('expires_at', '<', now())->delete());

        $this->registerRateLimiters();
        $this->registerAuthHooks();
    }

    private function registerRateLimiters(): void
    {
        RateLimiter::for('magic-link-request', fn (Request $request) => [
            Limit::perMinutes(15, 3)->by('ml-email:'.hash('sha256', EmailAddress::normalize($request->input('email')))),
            Limit::perHour(20)->by('ml-ip:'.$request->ip()),
        ]);
        RateLimiter::for('magic-link-redeem', fn (Request $request) => Limit::perMinute(10)->by('ml-redeem:'.$request->ip()));
        RateLimiter::for('password-login', fn (Request $request) => [
            Limit::perMinutes(15, 5)->by('pw-email:'.hash('sha256', EmailAddress::normalize($request->input('email')))),
            Limit::perMinute(10)->by('pw-ip:'.$request->ip()),
        ]);
        RateLimiter::for('two-factor', fn (Request $request) => [
            Limit::perMinute(5)->by('2fa-user:'.($request->session()->get('auth.pending_two_factor.user_id') ?? $request->user()?->getAuthIdentifier() ?? 'none')),
            Limit::perMinute(10)->by('2fa-ip:'.$request->ip()),
        ]);
        RateLimiter::for('account-security', fn (Request $request) => Limit::perMinute(5)->by('sec:'.($request->user()?->getAuthIdentifier() ?? $request->ip())));
        RateLimiter::for('passkeys', fn (Request $request) => Limit::perMinute(10)->by('pk:'.$request->ip()));
    }

    private function registerAuthHooks(): void
    {
        // Any successful passkey ceremony (login or confirmation) marks the session as passkey-verified.
        Event::listen(PasskeyVerified::class, function (PasskeyVerified $event): void {
            session()->put(EnsurePasskeySession::SESSION_KEY, now()->getTimestamp());
            session()->put('auth.method', 'passkey');
        });

        Event::listen(Login::class, function (Login $event): void {
            if (! $event->user instanceof User) {
                return;
            }
            $event->user->forceFill(['last_login_at' => now()])->save();
            AdminBootstrap::sync($event->user);
        });

        Passkeys::authorizeLoginUsing(function (Request $request, PasskeyUser $user, Passkey $passkey): bool {
            if ($user instanceof User && $user->isBlocked()) {
                throw ValidationException::withMessages(['credential' => [__('Dieses Konto ist gesperrt.')]]);
            }

            return true;
        });
    }
}
