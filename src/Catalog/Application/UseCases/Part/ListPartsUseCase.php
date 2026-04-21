<?php

namespace Domain\Catalog\Application\UseCases\Part;

use Domain\Catalog\Domain\Repositories\PartRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class ListPartsUseCase
{
    public function __construct(private readonly PartRepositoryInterface $repository) {}

    public function execute(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }
}
