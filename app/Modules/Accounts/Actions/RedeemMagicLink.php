<?php

namespace App\Modules\Accounts\Actions;

use App\Models\User;
use App\Modules\Accounts\Models\MagicLink;
use App\Support\ActivityLogger;
use App\Support\Tokens;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Redeems a magic link atomically and returns the (possibly new) user.
 */
final class RedeemMagicLink
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function __invoke(string $token): ?User
    {
        if (! Tokens::isWellFormed($token)) {
            return null;
        }

        $hash = Tokens::hash($token);

        return DB::transaction(function () use ($hash) {
            // Atomic: a double click can only redeem once.
            $redeemed = MagicLink::query()
                ->where('token_hash', $hash)
                ->whereNull('used_at')
                ->where('expires_at', '>', now())
                ->update(['used_at' => now()]);

            if ($redeemed !== 1) {
                return null;
            }

            $email = (string) MagicLink::query()->where('token_hash', $hash)->value('email');

            $user = User::query()->where('email', $email)->first();

            if ($user === null) {
                $user = User::query()->create([
                    'email' => $email,
                    'name' => Str::limit(Str::before($email, '@'), 80, ''),
                ]);
                $user->forceFill(['email_verified_at' => now()])->save();
                $this->activity->log('user.registered', $user, ['method' => 'magic_link'], $user->id);
            } elseif ($user->email_verified_at === null) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            // A successful sign-in removes all other links of this address.
            MagicLink::query()->where('email', $email)->where('token_hash', '!=', $hash)->delete();

            return $user;
        });
    }
}
