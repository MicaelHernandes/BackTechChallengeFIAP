<?php

namespace Domain\Catalog\Presentation\Resources;

use Domain\Catalog\Domain\Entities\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Service $service */
        $service = $this->resource;

        return [
            'id'                => $service->getId(),
            'name'              => $service->getName(),
            'description'       => $service->getDescription(),
            'base_price'        => $service->getBasePrice(),
            'estimated_minutes' => $service->getEstimatedMinutes(),
            'is_active'         => $service->isActive(),
        ];
    }
}
