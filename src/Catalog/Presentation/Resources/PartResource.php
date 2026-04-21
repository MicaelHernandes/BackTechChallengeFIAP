<?php

namespace Domain\Catalog\Presentation\Resources;

use Domain\Catalog\Domain\Entities\Part;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Part $part */
        $part = $this->resource;

        return [
            'id'             => $part->getId(),
            'code'           => $part->getCode(),
            'name'           => $part->getName(),
            'description'    => $part->getDescription(),
            'unit_price'     => $part->getUnitPrice(),
            'stock_quantity' => $part->getStockQuantity(),
            'minimum_stock'  => $part->getMinimumStock(),
            'unit'           => $part->getUnit(),
            'low_stock'      => $part->getStockQuantity() <= $part->getMinimumStock(),
        ];
    }
}
