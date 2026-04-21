<?php

namespace Domain\Inventory\Application\Policies;

use App\Models\User;
use App\Enums\UserRole;
use Domain\Inventory\Application\Mail\PartOutOfStockMail;
use Domain\Inventory\Domain\Events\PartOutOfStockEvent;
use Illuminate\Support\Facades\Mail;

/**
 * POL: Notifica o setor de Compras quando uma solicitação de peças
 * fica com status OUT_OF_STOCK.
 */
class NotifyPurchasingPolicy
{
    public function handle(PartOutOfStockEvent $event): void
    {
        $purchasingUsers = User::where('role', UserRole::Purchasing->value)->get();

        foreach ($purchasingUsers as $user) {
            Mail::to($user->email)->send(new PartOutOfStockMail($event->partRequest));
        }
    }
}
