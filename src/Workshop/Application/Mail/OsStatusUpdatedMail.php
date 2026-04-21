<?php

namespace Domain\Workshop\Application\Mail;

use Domain\Workshop\Domain\Events\OsStatusUpdatedEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OsStatusUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly OsStatusUpdatedEvent $event,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Oficina] Atualização da OS #' . $this->event->orderService->getId()
                . ' — ' . $this->event->orderService->getStatus()->label(),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.workshop.os-status-updated',
        );
    }
}
