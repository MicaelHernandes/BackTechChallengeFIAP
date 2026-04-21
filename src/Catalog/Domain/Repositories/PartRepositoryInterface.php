<?php

namespace Domain\Catalog\Domain\Repositories;

use Domain\Catalog\Domain\Entities\Part;
use Illuminate\Pagination\LengthAwarePaginator;

interface PartRepositoryInterface
{
    public function findById(int $id): ?Part;

    public function findByCode(string $code): ?Part;

    /** @return LengthAwarePaginator<Part> */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function save(Part $part): Part;

    public function delete(int $id): void;
}
