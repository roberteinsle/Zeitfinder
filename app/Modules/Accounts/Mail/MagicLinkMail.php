<?php

namespace App\Modules\Accounts\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MagicLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $url, public int $minutes) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Dein Anmeldelink für :app', ['app' => config('app.name')]));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.magic-link');
    }
}
