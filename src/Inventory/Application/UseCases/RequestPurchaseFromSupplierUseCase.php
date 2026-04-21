<?php

namespace Domain\Inventory\Application\UseCases;

use Domain\Core\Domain\Exceptions\NotFoundException;
use Domain\Inventory\Domain\Entities\PartRequest;
use Domain\Inventory\Domain\Repositories\PartRequestRepositoryInterface;

/**
 * Setor de Compras solicita as peças ao fornecedor.
 * Transição: OUT_OF_STOCK → PURCHASING
 */
class RequestPurchaseFromSupplierUseCase
{
    public function __construct(
        private readonly PartRequestRepositoryInterface $repository,
    ) {}

    public function execute(int $partRequestId): PartRequest
    {
        $partRequest = $this->repository->findById($partRequestId)
            ?? throw NotFoundException::forEntity('Solicitação de Peças', $partRequestId);

        $partRequest->requestPurchase();

        return $this->repository->save($partRequest);
    }
}
