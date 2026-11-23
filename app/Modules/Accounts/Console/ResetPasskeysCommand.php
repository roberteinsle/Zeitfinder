<?php

namespace App\Modules\Accounts\Console;

use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Console\Command;

/**
 * Emergency: removes all passkeys of an account (e.g. an admin lost every device).
 */
class ResetPasskeysCommand extends Command
{
    protected $signature = 'zeitfinder:reset-passkeys {email}';

    protected $description = 'Remove all passkeys of an account';

    public function handle(ActivityLogger $activity): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $this->error('No account with this email.');

            return self::FAILURE;
        }

        $count = $user->passkeys()->delete();
        $activity->log('user.passkeys_reset', $user, ['count' => $count]);
        $this->info("Removed {$count} passkey(s).");

        return self::SUCCESS;
    }
}
