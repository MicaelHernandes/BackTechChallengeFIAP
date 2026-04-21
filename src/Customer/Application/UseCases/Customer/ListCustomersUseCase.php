<?php

namespace Domain\Customer\Application\UseCases\Customer;

use Domain\Customer\Domain\Repositories\CustomerRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class ListCustomersUseCase
{
    public function __construct(
        private readonly CustomerRepositoryInterface $repository,
    ) {}

    public function execute(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }
}
