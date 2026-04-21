<?php

namespace Domain\Inventory\Application\UseCases;

use Domain\Catalog\Domain\Repositories\PartRepositoryInterface;
use Domain\Core\Domain\Exceptions\NotFoundException;
use Domain\Inventory\Application\DTOs\CreatePartRequestDTO;
use Domain\Inventory\Domain\Entities\PartRequest;
use Domain\Inventory\Domain\Entities\PartRequestItem;
use Domain\Inventory\Domain\Events\PartOutOfStockEvent;
use Domain\Inventory\Domain\Repositories\PartRequestRepositoryInterface;
use Illuminate\Support\Facades\Event;

/**
 * Cria a solicitação de peças e imediatamente executa a CheckInventoryPolicy:
 *  - Se todas as peças têm estoque → RESERVED e decrementa o estoque.
 *  - Se alguma peça falta estoque → OUT_OF_STOCK e dispara PartOutOfStockEvent.
 */
class CreatePartRequestUseCase
{
    public function __construct(
        private readonly PartRequestRepositoryInterface $partRequestRepository,
        private readonly PartRepositoryInterface $partRepository,
    ) {}

    public function execute(CreatePartRequestDTO $dto): PartRequest
    {
        $items = [];
        foreach ($dto->items as $itemData) {
            $part = $this->partRepository->findById($itemData['part_id'])
                ?? throw NotFoundException::forEntity('Peça', $itemData['part_id']);

            $items[] = PartRequestItem::create(
                partId: $part->getId(),
                partName: $part->getName(),
                quantityRequested: (int) $itemData['quantity'],
            );
        }

        $partRequest = PartRequest::create(
            $dto->requestedByUserId,
            $dto->osId,
            $dto->notes,
            ...$items,
        );

        // CheckInventoryPolicy — inline na criação
        $allAvailable = true;
        foreach ($dto->items as $itemData) {
            $part = $this->partRepository->findById($itemData['part_id']);
            if (!$part->hasStock((int) $itemData['quantity'])) {
                $allAvailable = false;
                break;
            }
        }

        if ($allAvailable) {
            // Reserva peças decrementando o estoque
            foreach ($dto->items as $itemData) {
                $part = $this->partRepository->findById($itemData['part_id']);
                $part->decrementStock((int) $itemData['quantity']);
                $this->partRepository->save($part);
            }
            $partRequest->reserve();
        } else {
            $partRequest->markOutOfStock();
        }

        $saved = $this->partRequestRepository->save($partRequest);

        if ($saved->getStatus()->value === 'out_of_stock') {
            Event::dispatch(new PartOutOfStockEvent($saved));
        }

        return $saved;
    }
}
