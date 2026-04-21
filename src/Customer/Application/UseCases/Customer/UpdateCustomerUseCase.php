<?php

namespace Domain\Customer\Application\UseCases\Customer;

use Domain\Core\Domain\Exceptions\DomainException;
use Domain\Core\Domain\Exceptions\NotFoundException;
use Domain\Customer\Application\DTOs\UpdateCustomerDTO;
use Domain\Customer\Domain\Entities\Customer;
use Domain\Customer\Domain\Repositories\CustomerRepositoryInterface;

class UpdateCustomerUseCase
{
    public function __construct(
        private readonly CustomerRepositoryInterface $repository,
    ) {}

    public function execute(UpdateCustomerDTO $dto): Customer
    {
        $customer = $this->repository->findById($dto->id);

        if ($customer === null) {
            throw NotFoundException::forEntity('Cliente', $dto->id);
        }

        $existing = $this->repository->findByEmail($dto->email);
        if ($existing !== null && $existing->getId() !== $dto->id) {
            throw DomainException::because("Já existe outro cliente com o e-mail '{$dto->email}'.");
        }

        $customer->updateDetails(
            name: $dto->name,
            email: $dto->email,
            phone: $dto->phone,
            address: $dto->address,
        );

        return $this->repository->save($customer);
    }
}
