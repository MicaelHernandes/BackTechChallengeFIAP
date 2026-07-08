<?php

namespace Domain\Workshop\Domain\Repositories;

use Domain\Workshop\Domain\Entities\OrderService;
use Domain\Workshop\Domain\Enums\OsStatus;
use Illuminate\Pagination\LengthAwarePaginator;

interface OrderServiceRepositoryInterface
{
    public function findById(int $id): ?OrderService;

    /**
     * @param  OsStatus[]  $excludeStatuses  Ignorado quando $status !== null (o filtro explícito sempre prevalece).
     * @return LengthAwarePaginator<OrderService>
     */
    public function paginate(?OsStatus $status, int $perPage = 15, array $excludeStatuses = []): LengthAwarePaginator;

    public function save(OrderService $os): OrderService;
}
