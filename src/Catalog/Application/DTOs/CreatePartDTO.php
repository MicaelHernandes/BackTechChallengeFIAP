<?php

namespace Domain\Catalog\Application\DTOs;

final readonly class CreatePartDTO
{
    public function __construct(
        public string $code,
        public string $name,
        public float $unitPrice,
        public ?string $description,
        public int $stockQuantity,
        public int $minimumStock,
        public string $unit,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            code: $data['code'],
            name: $data['name'],
            unitPrice: (float) $data['unit_price'],
            description: $data['description'] ?? null,
            stockQuantity: (int) ($data['stock_quantity'] ?? 0),
            minimumStock: (int) ($data['minimum_stock'] ?? 0),
            unit: $data['unit'] ?? 'un',
        );
    }
}
