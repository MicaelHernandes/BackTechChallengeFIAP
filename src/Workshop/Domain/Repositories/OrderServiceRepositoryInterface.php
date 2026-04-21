<?php

namespace Domain\Workshop\Domain\Repositories;

use Domain\Workshop\Domain\Entities\OrderService;
use Domain\Workshop\Domain\Enums\OsStatus;
use Illuminate\Pagination\LengthAwarePaginator;

interface OrderServiceRepositoryInterface
{
    public function findById(int $id): ?OrderService;

    /** @return LengthAwarePaginator<OrderService> */
    public function paginate(?OsStatus $status, int $perPage = 15): LengthAwarePaginator;

    public function save(OrderService $os): OrderService;
}
