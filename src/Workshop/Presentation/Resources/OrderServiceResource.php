<?php

namespace Domain\Workshop\Presentation\Resources;

use Domain\Workshop\Domain\Entities\OrderService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OrderService
 */
class OrderServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var OrderService $os */
        $os = $this->resource;

        return [
            'id' => $os->getId(),
            'status' => $os->getStatus()->value,
            'status_label' => $os->getStatus()->label(),
            'public_status' => [
                'value' => $os->getStatus()->toPublicStatus()->value,
                'label' => $os->getStatus()->toPublicStatus()->label(),
            ],
            'customer_id' => $os->getCustomerId(),
            'vehicle_id' => $os->getVehicleId(),
            'complaint' => $os->getComplaint(),
            'mechanic_user_id' => $os->getMechanicUserId(),
            'budget' => $os->getBudget()?->toArray(),
            'service_items' => array_map(fn ($i) => $i->toArray(), $os->getServiceItems()),
            'part_items' => array_map(fn ($i) => $i->toArray(), $os->getPartItems()),
            'requested_services' => array_map(fn ($i) => $i->toArray(), $os->getRequestedServiceItems()),
            'requested_parts' => array_map(fn ($i) => $i->toArray(), $os->getRequestedPartItems()),
        ];
    }
}
