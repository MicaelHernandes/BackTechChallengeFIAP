<?php

namespace Domain\Customer\Application\UseCases\Customer;

use Domain\Core\Domain\Exceptions\NotFoundException;
use Domain\Customer\Domain\Repositories\CustomerRepositoryInterface;

class DeleteCustomerUseCase
{
    public function __construct(
        private readonly CustomerRepositoryInterface $repository,
    ) {}

    public function execute(int $id): void
    {
        if ($this->repository->findById($id) === null) {
            throw NotFoundException::forEntity('Cliente', $id);
        }

        $this->repository->delete($id);
    }
}
