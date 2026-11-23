<?php

namespace App\Modules\Accounts\Actions;

use App\Modules\Accounts\Mail\MagicLinkMail;
use App\Modules\Accounts\Models\MagicLink;
use App\Support\Tokens;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Creates a one-time sign-in link and mails it. Works for new and existing
 * addresses alike (the first sign-in creates the account), so the response never
 * reveals whether an account exists.
 */
final class RequestMagicLink
{
    public function __invoke(string $email): void
    {
        $minutes = (int) config('zeitfinder.magic_link_ttl_minutes', 15);
        [$plain, $hash] = Tokens::generate();

        DB::transaction(function () use ($email, $hash, $minutes) {
            // A new request invalidates older open links for the same address.
            MagicLink::query()->where('email', $email)->whereNull('used_at')->delete();

            MagicLink::query()->create([
                'email' => $email,
                'token_hash' => $hash,
                'expires_at' => now()->addMinutes($minutes),
            ]);
        });

        try {
            // route() uses the forced root URL from APP_URL, never the request's Host header.
            Mail::to($email)->send(new MagicLinkMail(route('magic-link.show', ['token' => $plain]), $minutes));
        } catch (Throwable $e) {
            // The transport message (e.g. SMTP reply) never contains the mail body, so the token stays out of the log.
            Log::error('Magic link mail could not be sent.', ['exception' => $e::class, 'message' => mb_substr((string) preg_replace('/[a-f0-9]{64}/i', '[redacted]', $e->getMessage()), 0, 500)]);
        }
    }
}
