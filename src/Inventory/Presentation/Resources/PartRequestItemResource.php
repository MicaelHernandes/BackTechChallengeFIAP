<?php

namespace Domain\Inventory\Presentation\Resources;

use Domain\Inventory\Domain\Entities\PartRequestItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PartRequestItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var PartRequestItem $item */
        $item = $this->resource;

        return [
            'id'                 => $item->getId(),
            'part_id'            => $item->getPartId(),
            'part_name'          => $item->getPartName(),
            'quantity_requested' => $item->getQuantityRequested(),
            'quantity_provided'  => $item->getQuantityProvided(),
        ];
    }
}
