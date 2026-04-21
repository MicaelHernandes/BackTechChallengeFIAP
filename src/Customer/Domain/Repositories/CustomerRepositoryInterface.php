<?php

namespace Domain\Customer\Domain\Repositories;

use Domain\Customer\Domain\Entities\Customer;
use Illuminate\Pagination\LengthAwarePaginator;

interface CustomerRepositoryInterface
{
    public function findById(int $id): ?Customer;

    public function findByDocument(string $document): ?Customer;

    public function findByEmail(string $email): ?Customer;

    /** @return LengthAwarePaginator<Customer> */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function save(Customer $customer): Customer;

    public function delete(int $id): void;
}
