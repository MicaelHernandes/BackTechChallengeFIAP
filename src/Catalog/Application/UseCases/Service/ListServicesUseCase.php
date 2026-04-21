<?php

namespace Domain\Catalog\Application\UseCases\Service;

use Domain\Catalog\Domain\Repositories\ServiceRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class ListServicesUseCase
{
    public function __construct(private readonly ServiceRepositoryInterface $repository) {}

    public function execute(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }
}
