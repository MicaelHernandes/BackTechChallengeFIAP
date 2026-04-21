<?php

namespace Domain\Inventory\Application\Policies;

use App\Models\User;
use Domain\Inventory\Application\Mail\PartsReadyForPickupMail;
use Domain\Inventory\Domain\Events\PartsAddedToInventoryEvent;
use Illuminate\Support\Facades\Mail;

/**
 * POL: Notifica o mecânico que solicitou as peças de que elas estão
 * prontas para retirada no almoxarifado.
 */
class NotifyMechanicPolicy
{
    public function handle(PartsAddedToInventoryEvent $event): void
    {
        $mechanic = User::find($event->partRequest->getRequestedByUserId());

        if ($mechanic === null) {
            return;
        }

        Mail::to($mechanic->email)->send(
            new PartsReadyForPickupMail($event->partRequest, $event->supplierName)
        );
    }
}
