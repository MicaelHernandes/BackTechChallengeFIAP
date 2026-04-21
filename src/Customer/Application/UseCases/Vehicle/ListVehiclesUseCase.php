<?php

namespace Domain\Customer\Application\UseCases\Vehicle;

use Domain\Customer\Domain\Repositories\VehicleRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class ListVehiclesUseCase
{
    public function __construct(
        private readonly VehicleRepositoryInterface $repository,
    ) {}

    public function execute(int $customerId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginateByCustomer($customerId, $perPage);
    }
}
