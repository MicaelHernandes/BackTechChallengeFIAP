<?php

namespace Domain\Customer\Domain\Repositories;

use Domain\Customer\Domain\Entities\Vehicle;
use Illuminate\Pagination\LengthAwarePaginator;

interface VehicleRepositoryInterface
{
    public function findById(int $id): ?Vehicle;

    public function findByPlate(string $plate): ?Vehicle;

    /** @return LengthAwarePaginator<Vehicle> */
    public function paginateByCustomer(int $customerId, int $perPage = 15): LengthAwarePaginator;

    public function save(Vehicle $vehicle): Vehicle;

    public function delete(int $id): void;
}
