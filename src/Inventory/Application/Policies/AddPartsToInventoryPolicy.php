<?php

namespace Domain\Inventory\Application\Policies;

use Domain\Catalog\Domain\Repositories\PartRepositoryInterface;
use Domain\Inventory\Domain\Events\PartsAddedToInventoryEvent;
use Domain\Inventory\Domain\Events\PartsReceivedFromSupplierEvent;
use Illuminate\Support\Facades\Event;

/**
 * POL: Após o recebimento de peças do fornecedor, adiciona a quantidade
 * ao estoque físico de cada peça e dispara PartsAddedToInventoryEvent.
 */
class AddPartsToInventoryPolicy
{
    public function __construct(
        private readonly PartRepositoryInterface $partRepository,
    ) {}

    public function handle(PartsReceivedFromSupplierEvent $event): void
    {
        foreach ($event->partRequest->getItems() as $item) {
            $part = $this->partRepository->findById($item->getPartId());

            if ($part === null) {
                continue;
            }

            $quantityReceived = $item->getQuantityProvided() > 0
                ? $item->getQuantityProvided()
                : $item->getQuantityRequested();

            $part->incrementStock($quantityReceived);
            $this->partRepository->save($part);
        }

        Event::dispatch(new PartsAddedToInventoryEvent($event->partRequest, $event->supplierName));
    }
}
