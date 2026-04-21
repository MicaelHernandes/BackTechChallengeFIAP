<?php

namespace Domain\Customer\Presentation\Resources;

use Domain\Customer\Domain\Entities\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Vehicle */
class VehicleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Vehicle $vehicle */
        $vehicle = $this->resource;

        return [
            'id'          => $vehicle->getId(),
            'plate'       => $vehicle->getPlate()->formatted(),
            'brand'       => $vehicle->getBrand(),
            'model'       => $vehicle->getModel(),
            'year'        => $vehicle->getYear(),
            'color'       => $vehicle->getColor(),
            'customer_id' => $vehicle->getCustomerId(),
        ];
    }
}
