<?php

namespace App\Support;

use Illuminate\Mail\SentMessage;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Logging and safety checks around sending mail, shared by all modules.
 *
 * Every mail leaves a line in the runtime log: "Mail sent." with the message id the
 * SMTP server accepted, or "… could not be sent." with the transport error. Mail
 * bodies and tokens are never logged; recipients are masked.
 */
final class MailDelivery
{
    /** Mailers that do not deliver; in production they would also write tokens into the log. */
    private const NON_DELIVERING = ['log', 'array'];

    public function canDeliver(string $mail): bool
    {
        $mailer = (string) config('mail.default');

        if (app()->isProduction() && in_array($mailer, self::NON_DELIVERING, true)) {
            Log::error('Mail is not configured, nothing was sent. Set MAIL_MAILER=smtp and the MAIL_* variables.', [
                'mail' => $mail,
                'mailer' => $mailer,
            ]);

            return false;
        }

        return true;
    }

    public function sent(string $mail, string $recipient, ?SentMessage $sent): void
    {
        Log::info('Mail sent.', [
            'mail' => $mail,
            'mailer' => (string) config('mail.default'),
            'recipient' => self::maskAddress($recipient),
            'message_id' => $sent?->getMessageId(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function failed(string $message, Throwable $e, array $context = []): void
    {
        // The transport message (e.g. the SMTP reply) is needed to diagnose; it never contains
        // the mail body. Anything that looks like a token is masked anyway.
        Log::error($message, [
            ...$context,
            'mailer' => (string) config('mail.default'),
            'exception' => $e::class,
            'message' => mb_substr((string) preg_replace('/[a-f0-9]{64}/i', '[redacted]', $e->getMessage()), 0, 500),
        ]);
    }

    /** erika@example.com → e***@example.com */
    public static function maskAddress(string $address): string
    {
        [$local, $domain] = array_pad(explode('@', $address, 2), 2, '');

        return mb_substr($local, 0, 1).'***@'.$domain;
    }
}
