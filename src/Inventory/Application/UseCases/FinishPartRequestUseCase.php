<?php

namespace Domain\Inventory\Application\UseCases;

use Domain\Core\Domain\Exceptions\NotFoundException;
use Domain\Inventory\Domain\Entities\PartRequest;
use Domain\Inventory\Domain\Repositories\PartRequestRepositoryInterface;

/**
 * Almoxarife finaliza a solicitação.
 * Transição: PICKED_UP → FINALIZED
 */
class FinishPartRequestUseCase
{
    public function __construct(
        private readonly PartRequestRepositoryInterface $repository,
    ) {}

    public function execute(int $partRequestId): PartRequest
    {
        $partRequest = $this->repository->findById($partRequestId)
            ?? throw NotFoundException::forEntity('Solicitação de Peças', $partRequestId);

        $partRequest->finalize();

        return $this->repository->save($partRequest);
    }
}
