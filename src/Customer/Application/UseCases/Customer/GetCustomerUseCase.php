<?php

namespace Domain\Customer\Application\UseCases\Customer;

use Domain\Core\Domain\Exceptions\NotFoundException;
use Domain\Customer\Domain\Entities\Customer;
use Domain\Customer\Domain\Repositories\CustomerRepositoryInterface;

class GetCustomerUseCase
{
    public function __construct(
        private readonly CustomerRepositoryInterface $repository,
    ) {}

    public function execute(int $id): Customer
    {
        $customer = $this->repository->findById($id);

        if ($customer === null) {
            throw NotFoundException::forEntity('Cliente', $id);
        }

        return $customer;
    }
}
