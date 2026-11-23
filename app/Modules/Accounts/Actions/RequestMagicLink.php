<?php

namespace App\Modules\Accounts\Actions;

use App\Modules\Accounts\Mail\MagicLinkMail;
use App\Modules\Accounts\Models\MagicLink;
use App\Support\MailDelivery;
use App\Support\Tokens;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Creates a one-time sign-in link and mails it. Works for new and existing
 * addresses alike (the first sign-in creates the account), so the response never
 * reveals whether an account exists.
 */
final class RequestMagicLink
{
    public function __construct(private readonly MailDelivery $delivery) {}

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

        if (! $this->delivery->canDeliver('magic_link')) {
            return;
        }

        try {
            // route() uses the forced root URL from APP_URL, never the request's Host header.
            $sent = Mail::to($email)->send(new MagicLinkMail(route('magic-link.show', ['token' => $plain]), $minutes));
            $this->delivery->sent('magic_link', $email, $sent);
        } catch (Throwable $e) {
            $this->delivery->failed('Magic link mail could not be sent.', $e);
        }
    }
}
