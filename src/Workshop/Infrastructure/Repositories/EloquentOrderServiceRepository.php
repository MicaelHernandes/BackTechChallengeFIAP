<?php

namespace Domain\Workshop\Infrastructure\Repositories;

use Domain\Workshop\Domain\Entities\Budget;
use Domain\Workshop\Domain\Entities\OrderService;
use Domain\Workshop\Domain\Entities\OsPartItem;
use Domain\Workshop\Domain\Entities\OsRequestedPartItem;
use Domain\Workshop\Domain\Entities\OsRequestedServiceItem;
use Domain\Workshop\Domain\Entities\OsServiceItem;
use Domain\Workshop\Domain\Enums\OsStatus;
use Domain\Workshop\Domain\Repositories\OrderServiceRepositoryInterface;
use Domain\Workshop\Infrastructure\Models\BudgetModel;
use Domain\Workshop\Infrastructure\Models\OrderServiceModel;
use Domain\Workshop\Infrastructure\Models\OsPartItemModel;
use Domain\Workshop\Infrastructure\Models\OsRequestedPartItemModel;
use Domain\Workshop\Infrastructure\Models\OsRequestedServiceItemModel;
use Domain\Workshop\Infrastructure\Models\OsServiceItemModel;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentOrderServiceRepository implements OrderServiceRepositoryInterface
{
    private const array EAGER_LOAD = [
        'budget', 'serviceItems', 'partItems', 'requestedServiceItems', 'requestedPartItems',
    ];

    public function findById(int $id): ?OrderService
    {
        $model = OrderServiceModel::with(self::EAGER_LOAD)->find($id);

        return $model ? $this->toEntity($model) : null;
    }

    public function paginate(?OsStatus $status, int $perPage = 15, array $excludeStatuses = []): LengthAwarePaginator
    {
        $query = OrderServiceModel::with(self::EAGER_LOAD);

        if ($status !== null) {
            $query->where('status', $status->value);
        } elseif (! empty($excludeStatuses)) {
            $query->whereNotIn('status', array_map(fn (OsStatus $s) => $s->value, $excludeStatuses));
        }

        $query->orderByRaw($this->statusPriorityOrderExpression())
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc');

        $paginator = $query->paginate($perPage);
        $paginator->getCollection()->transform(fn ($m) => $this->toEntity($m));

        return $paginator;
    }

    /**
     * CASE portável entre PostgreSQL (produção) e SQLite (testes), gerado a
     * partir de OsStatus::cases() + toPublicStatus()->priority() para nunca
     * duplicar a regra de prioridade entre PHP e SQL.
     */
    private function statusPriorityOrderExpression(): string
    {
        $whens = array_map(
            fn (OsStatus $s) => sprintf("WHEN '%s' THEN %d", $s->value, $s->toPublicStatus()->priority()),
            OsStatus::cases()
        );

        return 'CASE status '.implode(' ', $whens).' ELSE 99 END ASC';
    }

    public function save(OrderService $os): OrderService
    {
        $data = [
            'status' => $os->getStatus()->value,
            'customer_id' => $os->getCustomerId(),
            'vehicle_id' => $os->getVehicleId(),
            'complaint' => $os->getComplaint(),
            'mechanic_user_id' => $os->getMechanicUserId(),
            'started_at' => $os->getStartedAt()?->format('Y-m-d H:i:s'),
            'finished_at' => $os->getFinishedAt()?->format('Y-m-d H:i:s'),
        ];

        if ($os->getId() !== null) {
            $model = OrderServiceModel::find($os->getId());
            $model->update($data);
        } else {
            $model = OrderServiceModel::create($data);
        }

        // Upsert budget
        $budget = $os->getBudget();
        if ($budget !== null) {
            BudgetModel::updateOrCreate(
                ['os_id' => $model->id],
                [
                    'total_services' => $budget->getTotalServices(),
                    'total_parts' => $budget->getTotalParts(),
                    'total_amount' => $budget->getTotalAmount(),
                    'notes' => $budget->getNotes(),
                ]
            );
        }

        // Replace service items (delete + re-insert on each budget generation)
        if (! empty($os->getServiceItems())) {
            OsServiceItemModel::where('os_id', $model->id)->delete();
            foreach ($os->getServiceItems() as $item) {
                OsServiceItemModel::create([
                    'os_id' => $model->id,
                    'service_id' => $item->getServiceId(),
                    'service_name' => $item->getServiceName(),
                    'quantity' => $item->getQuantity(),
                    'unit_price' => $item->getUnitPrice(),
                ]);
            }
        }

        // Replace part items
        if (! empty($os->getPartItems())) {
            OsPartItemModel::where('os_id', $model->id)->delete();
            foreach ($os->getPartItems() as $item) {
                OsPartItemModel::create([
                    'os_id' => $model->id,
                    'part_id' => $item->getPartId(),
                    'part_name' => $item->getPartName(),
                    'quantity' => $item->getQuantity(),
                    'unit_price' => $item->getUnitPrice(),
                ]);
            }
        }

        // Replace requested service items (definidos apenas na criação da OS)
        if (! empty($os->getRequestedServiceItems())) {
            OsRequestedServiceItemModel::where('os_id', $model->id)->delete();
            foreach ($os->getRequestedServiceItems() as $item) {
                OsRequestedServiceItemModel::create([
                    'os_id' => $model->id,
                    'service_id' => $item->getServiceId(),
                    'service_name' => $item->getServiceName(),
                    'quantity' => $item->getQuantity(),
                ]);
            }
        }

        // Replace requested part items
        if (! empty($os->getRequestedPartItems())) {
            OsRequestedPartItemModel::where('os_id', $model->id)->delete();
            foreach ($os->getRequestedPartItems() as $item) {
                OsRequestedPartItemModel::create([
                    'os_id' => $model->id,
                    'part_id' => $item->getPartId(),
                    'part_name' => $item->getPartName(),
                    'quantity' => $item->getQuantity(),
                ]);
            }
        }

        $model->load(self::EAGER_LOAD);

        return $this->toEntity($model);
    }

    private function toEntity(OrderServiceModel $model): OrderService
    {
        $os = new OrderService(
            id: $model->id,
            status: OsStatus::from($model->status),
            customerId: $model->customer_id,
            vehicleId: $model->vehicle_id,
            complaint: $model->complaint,
            mechanicUserId: $model->mechanic_user_id,
            startedAt: $model->started_at
                ? \DateTimeImmutable::createFromMutable($model->started_at->toDateTime())
                : null,
            finishedAt: $model->finished_at
                ? \DateTimeImmutable::createFromMutable($model->finished_at->toDateTime())
                : null,
        );

        if ($model->budget) {
            $b = $model->budget;
            $os->setBudget(new Budget(
                id: $b->id,
                osId: $b->os_id,
                totalServices: (float) $b->total_services,
                totalParts: (float) $b->total_parts,
                totalAmount: (float) $b->total_amount,
                notes: $b->notes,
            ));
        }

        $serviceItems = $model->serviceItems->map(fn (OsServiceItemModel $m) => new OsServiceItem(
            id: $m->id,
            serviceId: $m->service_id,
            serviceName: $m->service_name,
            quantity: $m->quantity,
            unitPrice: (float) $m->unit_price,
        ))->all();

        $partItems = $model->partItems->map(fn (OsPartItemModel $m) => new OsPartItem(
            id: $m->id,
            partId: $m->part_id,
            partName: $m->part_name,
            quantity: $m->quantity,
            unitPrice: (float) $m->unit_price,
        ))->all();

        $requestedServiceItems = $model->requestedServiceItems->map(fn (OsRequestedServiceItemModel $m) => new OsRequestedServiceItem(
            id: $m->id,
            serviceId: $m->service_id,
            serviceName: $m->service_name,
            quantity: $m->quantity,
        ))->all();

        $requestedPartItems = $model->requestedPartItems->map(fn (OsRequestedPartItemModel $m) => new OsRequestedPartItem(
            id: $m->id,
            partId: $m->part_id,
            partName: $m->part_name,
            quantity: $m->quantity,
        ))->all();

        $os->setServiceItems($serviceItems);
        $os->setPartItems($partItems);
        $os->setRequestedServiceItems($requestedServiceItems);
        $os->setRequestedPartItems($requestedPartItems);

        return $os;
    }
}
