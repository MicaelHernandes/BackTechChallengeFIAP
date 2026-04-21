<?php

namespace Domain\Customer\Application\UseCases\Customer;

use Domain\Core\Domain\Exceptions\DomainException;
use Domain\Customer\Application\DTOs\CreateCustomerDTO;
use Domain\Customer\Domain\Entities\Customer;
use Domain\Customer\Domain\Repositories\CustomerRepositoryInterface;

class CreateCustomerUseCase
{
    public function __construct(
        private readonly CustomerRepositoryInterface $repository,
    ) {}

    public function execute(CreateCustomerDTO $dto): Customer
    {
        if ($this->repository->findByDocument($dto->document) !== null) {
            throw DomainException::because("Já existe um cliente com o documento '{$dto->document}'.");
        }

        if ($this->repository->findByEmail($dto->email) !== null) {
            throw DomainException::because("Já existe um cliente com o e-mail '{$dto->email}'.");
        }

        $customer = Customer::create(
            name: $dto->name,
            document: $dto->document,
            email: $dto->email,
            phone: $dto->phone,
            address: $dto->address,
        );

        return $this->repository->save($customer);
    }
}
