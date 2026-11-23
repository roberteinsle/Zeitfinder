<?php

namespace App\Modules\Notifications\Mail;

use App\Modules\Polls\Models\Poll;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PollCreatedMail extends Mailable
{
    public function __construct(public Poll $poll, public string $publicUrl, public string $adminUrl) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Deine Umfrage „:title“ ist bereit', ['title' => $this->poll->title]));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.poll-created');
    }
}
