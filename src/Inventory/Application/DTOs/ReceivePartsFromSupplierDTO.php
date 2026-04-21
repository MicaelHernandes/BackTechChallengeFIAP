<?php

namespace Domain\Inventory\Application\DTOs;

final readonly class ReceivePartsFromSupplierDTO
{
    /** @param array<array{part_request_item_id:int, quantity_received:int}> $items */
    public function __construct(
        public int $partRequestId,
        public string $supplierName,
        public array $items,
    ) {}

    public static function fromArray(int $id, array $data): self
    {
        return new self(
            partRequestId: $id,
            supplierName: $data['supplier_name'],
            items: $data['items'],
        );
    }
}
