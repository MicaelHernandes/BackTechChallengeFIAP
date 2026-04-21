<?php

namespace Domain\Inventory\Application\Mail;

use Domain\Inventory\Domain\Entities\PartRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PartOutOfStockMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly PartRequest $partRequest,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Oficina] Solicitação de Peças — Estoque Insuficiente #' . $this->partRequest->getId(),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.inventory.part-out-of-stock',
        );
    }
}
