<?php

namespace Domain\Customer\Infrastructure\Repositories;

use Domain\Customer\Domain\Entities\Customer;
use Domain\Customer\Domain\Repositories\CustomerRepositoryInterface;
use Domain\Customer\Domain\ValueObjects\CpfCnpj;
use Domain\Customer\Infrastructure\Models\CustomerModel;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentCustomerRepository implements CustomerRepositoryInterface
{
    public function findById(int $id): ?Customer
    {
        $model = CustomerModel::find($id);

        return $model ? $this->toEntity($model) : null;
    }

    public function findByDocument(string $document): ?Customer
    {
        $clean = preg_replace('/\D/', '', $document);
        $model = CustomerModel::where('document', $clean)->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function findByEmail(string $email): ?Customer
    {
        $model = CustomerModel::where('email', $email)->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        $paginator = CustomerModel::orderBy('name')->paginate($perPage);

        $paginator->getCollection()->transform(fn ($m) => $this->toEntity($m));

        return $paginator;
    }

    public function save(Customer $customer): Customer
    {
        $data = [
            'name'     => $customer->getName(),
            'document' => $customer->getDocument()->value(),
            'email'    => $customer->getEmail(),
            'phone'    => $customer->getPhone(),
            'address'  => $customer->getAddress(),
        ];

        if ($customer->getId() !== null) {
            CustomerModel::where('id', $customer->getId())->update($data);
            $model = CustomerModel::find($customer->getId());
        } else {
            $model = CustomerModel::create($data);
        }

        return $this->toEntity($model);
    }

    public function delete(int $id): void
    {
        CustomerModel::destroy($id);
    }

    private function toEntity(CustomerModel $model): Customer
    {
        return new Customer(
            id: $model->id,
            name: $model->name,
            document: new CpfCnpj($model->document),
            email: $model->email,
            phone: $model->phone,
            address: $model->address,
        );
    }
}
