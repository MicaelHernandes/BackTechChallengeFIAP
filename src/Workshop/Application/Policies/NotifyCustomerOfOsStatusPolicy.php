<?php

namespace Domain\Workshop\Application\Policies;

use Domain\Workshop\Application\Mail\OsStatusUpdatedMail;
use Domain\Workshop\Domain\Events\OsStatusUpdatedEvent;
use Illuminate\Support\Facades\Mail;

/**
 * POL: Notifica o cliente por e-mail a cada mudança de status da OS.
 * Escuta: OsStatusUpdatedEvent
 * Dispara: OsStatusUpdatedMail via Mailpit
 *
 * Eventos cobertos:
 *   Criou OS | Em Análise | Orçamento Pendente | Renegociação Pendente
 *   Aprovado | Execução Iniciada | Execução Finalizada | OS Finalizada/Entregue
 */
class NotifyCustomerOfOsStatusPolicy
{
    public function handle(OsStatusUpdatedEvent $event): void
    {
        Mail::to($event->customerEmail)
            ->send(new OsStatusUpdatedMail($event));
    }
}
