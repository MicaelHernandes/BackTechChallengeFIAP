<?php

namespace Domain\Inventory\Application\UseCases;

use Domain\Core\Domain\Exceptions\NotFoundException;
use Domain\Inventory\Application\DTOs\ReceivePartsFromSupplierDTO;
use Domain\Inventory\Domain\Entities\PartRequest;
use Domain\Inventory\Domain\Events\PartsReceivedFromSupplierEvent;
use Domain\Inventory\Domain\Repositories\PartRequestRepositoryInterface;
use Illuminate\Support\Facades\Event;

/**
 * Setor de Compras registra o recebimento de peças do fornecedor.
 * Transição: PURCHASING → AVAILABLE_FOR_PICKUP
 * Dispara: PartsReceivedFromSupplierEvent → AddPartsToInventoryPolicy → NotifyMechanicPolicy
 */
class ReceivePartsFromSupplierUseCase
{
    public function __construct(
        private readonly PartRequestRepositoryInterface $repository,
    ) {}

    public function execute(ReceivePartsFromSupplierDTO $dto): PartRequest
    {
        $partRequest = $this->repository->findById($dto->partRequestId)
            ?? throw NotFoundException::forEntity('Solicitação de Peças', $dto->partRequestId);

        // Atualiza quantidades fornecidas em cada item
        foreach ($dto->items as $itemData) {
            foreach ($partRequest->getItems() as $item) {
                if ($item->getId() === (int) $itemData['part_request_item_id']) {
                    $item->setQuantityProvided((int) $itemData['quantity_received']);
                    break;
                }
            }
        }

        $partRequest->markAvailableForPickup();

        $saved = $this->repository->save($partRequest);

        // AddPartsToInventoryPolicy + NotifyMechanicPolicy são disparadas via evento
        Event::dispatch(new PartsReceivedFromSupplierEvent($saved, $dto->supplierName));

        return $saved;
    }
}
