<?php

namespace Domain\Customer\Infrastructure\Repositories;

use Domain\Customer\Domain\Entities\Vehicle;
use Domain\Customer\Domain\Repositories\VehicleRepositoryInterface;
use Domain\Customer\Domain\ValueObjects\LicensePlate;
use Domain\Customer\Infrastructure\Models\VehicleModel;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentVehicleRepository implements VehicleRepositoryInterface
{
    public function findById(int $id): ?Vehicle
    {
        $model = VehicleModel::find($id);

        return $model ? $this->toEntity($model) : null;
    }

    public function findByPlate(string $plate): ?Vehicle
    {
        $normalized = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $plate));
        $model = VehicleModel::where('plate', $normalized)->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function paginateByCustomer(int $customerId, int $perPage = 15): LengthAwarePaginator
    {
        $paginator = VehicleModel::where('customer_id', $customerId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $paginator->getCollection()->transform(fn ($m) => $this->toEntity($m));

        return $paginator;
    }

    public function save(Vehicle $vehicle): Vehicle
    {
        $data = [
            'customer_id' => $vehicle->getCustomerId(),
            'plate'       => $vehicle->getPlate()->value(),
            'brand'       => $vehicle->getBrand(),
            'model'       => $vehicle->getModel(),
            'year'        => $vehicle->getYear(),
            'color'       => $vehicle->getColor(),
        ];

        if ($vehicle->getId() !== null) {
            VehicleModel::where('id', $vehicle->getId())->update($data);
            $model = VehicleModel::find($vehicle->getId());
        } else {
            $model = VehicleModel::create($data);
        }

        return $this->toEntity($model);
    }

    public function delete(int $id): void
    {
        VehicleModel::destroy($id);
    }

    private function toEntity(VehicleModel $model): Vehicle
    {
        return new Vehicle(
            id: $model->id,
            plate: new LicensePlate($model->plate),
            brand: $model->brand,
            model: $model->model,
            year: $model->year,
            color: $model->color,
            customerId: $model->customer_id,
        );
    }
}
