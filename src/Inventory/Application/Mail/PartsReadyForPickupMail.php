<?php

namespace Domain\Inventory\Application\Mail;

use Domain\Inventory\Domain\Entities\PartRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PartsReadyForPickupMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly PartRequest $partRequest,
        public readonly string $supplierName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Oficina] Peças Disponíveis para Retirada — Solicitação #' . $this->partRequest->getId(),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.inventory.parts-ready-for-pickup',
        );
    }
}
