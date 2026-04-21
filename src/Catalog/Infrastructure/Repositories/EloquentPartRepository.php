<?php

namespace Domain\Catalog\Infrastructure\Repositories;

use Domain\Catalog\Domain\Entities\Part;
use Domain\Catalog\Domain\Repositories\PartRepositoryInterface;
use Domain\Catalog\Infrastructure\Models\PartModel;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentPartRepository implements PartRepositoryInterface
{
    public function findById(int $id): ?Part
    {
        $model = PartModel::find($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function findByCode(string $code): ?Part
    {
        $model = PartModel::where('code', $code)->first();
        return $model ? $this->toEntity($model) : null;
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        $paginator = PartModel::orderBy('name')->paginate($perPage);
        $paginator->getCollection()->transform(fn ($m) => $this->toEntity($m));
        return $paginator;
    }

    public function save(Part $part): Part
    {
        $data = [
            'code'           => $part->getCode(),
            'name'           => $part->getName(),
            'description'    => $part->getDescription(),
            'unit_price'     => $part->getUnitPrice(),
            'stock_quantity' => $part->getStockQuantity(),
            'minimum_stock'  => $part->getMinimumStock(),
            'unit'           => $part->getUnit(),
        ];

        if ($part->getId() !== null) {
            PartModel::where('id', $part->getId())->update($data);
            $model = PartModel::find($part->getId());
        } else {
            $model = PartModel::create($data);
        }

        return $this->toEntity($model);
    }

    public function delete(int $id): void
    {
        PartModel::destroy($id);
    }

    private function toEntity(PartModel $model): Part
    {
        return new Part(
            id: $model->id,
            code: $model->code,
            name: $model->name,
            description: $model->description,
            unitPrice: (float) $model->unit_price,
            stockQuantity: (int) $model->stock_quantity,
            minimumStock: (int) $model->minimum_stock,
            unit: $model->unit,
        );
    }
}
