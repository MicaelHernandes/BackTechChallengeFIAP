<?php

namespace Domain\Inventory\Infrastructure\Repositories;

use Domain\Inventory\Domain\Entities\PartRequest;
use Domain\Inventory\Domain\Entities\PartRequestItem;
use Domain\Inventory\Domain\Enums\PartRequestStatus;
use Domain\Inventory\Domain\Repositories\PartRequestRepositoryInterface;
use Domain\Inventory\Infrastructure\Models\PartRequestItemModel;
use Domain\Inventory\Infrastructure\Models\PartRequestModel;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentPartRequestRepository implements PartRequestRepositoryInterface
{
    public function findById(int $id): ?PartRequest
    {
        $model = PartRequestModel::with('items')->find($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function paginate(?PartRequestStatus $status, int $perPage = 15): LengthAwarePaginator
    {
        $query = PartRequestModel::with('items')->orderByDesc('created_at');

        if ($status !== null) {
            $query->where('status', $status->value);
        }

        $paginator = $query->paginate($perPage);
        $paginator->getCollection()->transform(fn ($m) => $this->toEntity($m));

        return $paginator;
    }

    public function save(PartRequest $partRequest): PartRequest
    {
        $data = [
            'status'               => $partRequest->getStatus()->value,
            'requested_by_user_id' => $partRequest->getRequestedByUserId(),
            'os_id'                => $partRequest->getOsId(),
            'notes'                => $partRequest->getNotes(),
        ];

        if ($partRequest->getId() !== null) {
            $model = PartRequestModel::find($partRequest->getId());
            $model->update($data);
        } else {
            $model = PartRequestModel::create($data);
        }

        // Upsert items
        foreach ($partRequest->getItems() as $item) {
            if ($item->getId() !== null) {
                PartRequestItemModel::where('id', $item->getId())->update([
                    'quantity_provided' => $item->getQuantityProvided(),
                ]);
            } else {
                $itemModel = PartRequestItemModel::create([
                    'part_request_id'    => $model->id,
                    'part_id'            => $item->getPartId(),
                    'part_name'          => $item->getPartName(),
                    'quantity_requested' => $item->getQuantityRequested(),
                    'quantity_provided'  => $item->getQuantityProvided(),
                ]);
            }
        }

        $model->load('items');

        return $this->toEntity($model);
    }

    public function countByOsIdAndStatuses(int $osId, array $statusValues): int
    {
        return PartRequestModel::where('os_id', $osId)
            ->whereIn('status', $statusValues)
            ->count();
    }

    public function countByOsId(int $osId): int
    {
        return PartRequestModel::where('os_id', $osId)->count();
    }

    private function toEntity(PartRequestModel $model): PartRequest
    {
        $items = $model->items->map(function (PartRequestItemModel $itemModel) {
            return new PartRequestItem(
                id: $itemModel->id,
                partId: $itemModel->part_id,
                partName: $itemModel->part_name,
                quantityRequested: $itemModel->quantity_requested,
                quantityProvided: $itemModel->quantity_provided,
            );
        })->all();

        return new PartRequest(
            $model->id,
            PartRequestStatus::from($model->status),
            $model->requested_by_user_id,
            $model->os_id,
            $model->notes,
            ...$items,
        );
    }
}
