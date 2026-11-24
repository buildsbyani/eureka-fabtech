<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Every Eureka email: a subject, a Blade view under resources/views/emails,
 * its data, and an optional calendar invite.
 */
class NoticeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $template,
        public array $data = [],
        public ?string $ics = null,
        public string $icsMethod = 'REQUEST',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.'.$this->template, with: $this->data);
    }

    public function attachments(): array
    {
        if (! $this->ics) {
            return [];
        }

        return [
            Attachment::fromData(fn () => $this->ics, $this->icsMethod === 'CANCEL' ? 'cancel.ics' : 'invite.ics')
                ->withMime('text/calendar; charset=UTF-8; method='.$this->icsMethod),
        ];
    }
}
