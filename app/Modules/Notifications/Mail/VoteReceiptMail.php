<?php

namespace App\Modules\Notifications\Mail;

use App\Modules\Polls\Models\Poll;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Confirmation for a participant with the personal edit link. Also used after a
 * duplicate attempt ($duplicate = true).
 */
class VoteReceiptMail extends Mailable
{
    public function __construct(public Poll $poll, public string $name, public string $editUrl, public bool $duplicate = false) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->duplicate
            ? __('Deine Antwort bei „:title“ ändern', ['title' => $this->poll->title])
            : __('Deine Antwort bei „:title“', ['title' => $this->poll->title]));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.vote-receipt');
    }
}
