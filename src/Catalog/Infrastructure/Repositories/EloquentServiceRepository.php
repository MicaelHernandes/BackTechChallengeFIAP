<?php

namespace Domain\Catalog\Infrastructure\Repositories;

use Domain\Catalog\Domain\Entities\Service;
use Domain\Catalog\Domain\Repositories\ServiceRepositoryInterface;
use Domain\Catalog\Infrastructure\Models\ServiceModel;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentServiceRepository implements ServiceRepositoryInterface
{
    public function findById(int $id): ?Service
    {
        $model = ServiceModel::find($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        $paginator = ServiceModel::orderBy('name')->paginate($perPage);
        $paginator->getCollection()->transform(fn ($m) => $this->toEntity($m));
        return $paginator;
    }

    public function save(Service $service): Service
    {
        $data = [
            'name'              => $service->getName(),
            'description'       => $service->getDescription(),
            'base_price'        => $service->getBasePrice(),
            'estimated_minutes' => $service->getEstimatedMinutes(),
            'is_active'         => $service->isActive(),
        ];

        if ($service->getId() !== null) {
            ServiceModel::where('id', $service->getId())->update($data);
            $model = ServiceModel::find($service->getId());
        } else {
            $model = ServiceModel::create($data);
        }

        return $this->toEntity($model);
    }

    public function delete(int $id): void
    {
        ServiceModel::destroy($id);
    }

    private function toEntity(ServiceModel $model): Service
    {
        return new Service(
            id: $model->id,
            name: $model->name,
            description: $model->description,
            basePrice: (float) $model->base_price,
            estimatedMinutes: $model->estimated_minutes,
            isActive: (bool) $model->is_active,
        );
    }
}
