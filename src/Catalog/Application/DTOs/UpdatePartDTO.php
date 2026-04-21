<?php

namespace Domain\Catalog\Application\DTOs;

final readonly class UpdatePartDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public float $unitPrice,
        public ?string $description,
        public int $minimumStock,
        public string $unit,
    ) {}

    public static function fromArray(int $id, array $data): self
    {
        return new self(
            id: $id,
            name: $data['name'],
            unitPrice: (float) $data['unit_price'],
            description: $data['description'] ?? null,
            minimumStock: (int) ($data['minimum_stock'] ?? 0),
            unit: $data['unit'] ?? 'un',
        );
    }
}
