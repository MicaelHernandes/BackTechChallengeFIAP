<?php

namespace Domain\Inventory\Application\UseCases;

use Domain\Catalog\Domain\Repositories\PartRepositoryInterface;
use Domain\Core\Domain\Exceptions\NotFoundException;
use Domain\Inventory\Domain\Entities\PartRequest;
use Domain\Inventory\Domain\Enums\PartRequestStatus;
use Domain\Inventory\Domain\Repositories\PartRequestRepositoryInterface;

/**
 * Mecânico retira as peças para execução da OS.
 * Transição: RESERVED | AVAILABLE_FOR_PICKUP → PICKED_UP
 * Se veio de AVAILABLE_FOR_PICKUP, decrementa o estoque (já foi incrementado pelo fornecedor).
 */
class PickUpPartsUseCase
{
    public function __construct(
        private readonly PartRequestRepositoryInterface $repository,
        private readonly PartRepositoryInterface $partRepository,
    ) {}

    public function execute(int $partRequestId): PartRequest
    {
        $partRequest = $this->repository->findById($partRequestId)
            ?? throw NotFoundException::forEntity('Solicitação de Peças', $partRequestId);

        $cameFromAvailable = $partRequest->getStatus() === PartRequestStatus::AvailableForPickup;

        $partRequest->pickUp();

        // Only decrement stock when the request is linked to an OS (parts consumed for a job).
        // Standalone restock purchases (no os_id) should remain in stock after pickup.
        if ($cameFromAvailable && $partRequest->getOsId() !== null) {
            foreach ($partRequest->getItems() as $item) {
                $part = $this->partRepository->findById($item->getPartId());
                if ($part !== null) {
                    $provided = $item->getQuantityProvided() > 0
                        ? $item->getQuantityProvided()
                        : $item->getQuantityRequested();

                    $part->decrementStock($provided);
                    $this->partRepository->save($part);
                }
            }
        }

        return $this->repository->save($partRequest);
    }
}
