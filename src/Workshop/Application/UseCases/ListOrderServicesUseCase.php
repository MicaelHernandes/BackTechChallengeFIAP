<?php

namespace Domain\Workshop\Application\UseCases;

use Domain\Workshop\Domain\Enums\OsStatus;
use Domain\Workshop\Domain\Repositories\OrderServiceRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * AG: Listagem de OS
 * Sem filtro explícito de status, oculta as OS em estados considerados
 * "encerrados para fins de listagem" (Rejected, ExecutionFinished,
 * DeliveredAndFinalized) — exclusão lógica, os registros continuam
 * acessíveis normalmente via show() ou filtro explícito de status.
 */
class ListOrderServicesUseCase
{
    public function __construct(
        private readonly OrderServiceRepositoryInterface $repository,
    ) {}

    public function execute(?OsStatus $status, int $perPage = 15): LengthAwarePaginator
    {
        $excludeStatuses = $status === null
            ? array_values(array_filter(
                OsStatus::cases(),
                fn (OsStatus $s) => $s->isHiddenFromDefaultListing()
            ))
            : [];

        return $this->repository->paginate($status, $perPage, $excludeStatuses);
    }
}
