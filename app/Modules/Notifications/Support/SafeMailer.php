<?php

namespace App\Modules\Notifications\Support;

use App\Support\ActivityLogger;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends mails after the response. A failed mail is logged and reported in the
 * activity log, but never undoes the action that triggered it.
 */
final class SafeMailer
{
    public function __construct(private readonly ActivityLogger $activity) {}

    /**
     * @param  array<string, Mailable>  $mails  recipient => mailable
     */
    public function sendLater(array $mails, string $context, ?int $pollId = null): void
    {
        if ($mails === []) {
            return;
        }

        defer(function () use ($mails, $context, $pollId) {
            $failed = 0;
            foreach ($mails as $recipient => $mail) {
                try {
                    Mail::to($recipient)->send($mail);
                } catch (Throwable $e) {
                    $failed++;
                    // The transport message (e.g. SMTP reply) is needed to diagnose; it never contains the mail body.
                    Log::error('Notification mail failed.', ['context' => $context, 'poll_id' => $pollId, 'exception' => $e::class, 'message' => mb_substr((string) preg_replace('/[a-f0-9]{64}/i', '[redacted]', $e->getMessage()), 0, 500)]);
                }
            }

            if ($failed > 0) {
                $this->activity->log('mail.failed', null, ['context' => $context, 'poll_id' => $pollId, 'failed' => $failed, 'total' => count($mails)]);
            }
        });
    }
}
