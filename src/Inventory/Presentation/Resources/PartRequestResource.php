<?php

namespace Domain\Inventory\Presentation\Resources;

use Domain\Inventory\Domain\Entities\PartRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PartRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var PartRequest $pr */
        $pr = $this->resource;

        return [
            'id'                   => $pr->getId(),
            'status'               => $pr->getStatus()->value,
            'status_label'         => $pr->getStatus()->label(),
            'requested_by_user_id' => $pr->getRequestedByUserId(),
            'os_id'                => $pr->getOsId(),
            'notes'                => $pr->getNotes(),
            'items'                => PartRequestItemResource::collection($pr->getItems()),
        ];
    }
}
