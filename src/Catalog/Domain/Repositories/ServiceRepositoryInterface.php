<?php

namespace Domain\Catalog\Domain\Repositories;

use Domain\Catalog\Domain\Entities\Service;
use Illuminate\Pagination\LengthAwarePaginator;

interface ServiceRepositoryInterface
{
    public function findById(int $id): ?Service;

    /** @return LengthAwarePaginator<Service> */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function save(Service $service): Service;

    public function delete(int $id): void;
}
