<?php

namespace Domain\Catalog\Application\DTOs;

final readonly class CreateServiceDTO
{
    public function __construct(
        public string $name,
        public float $basePrice,
        public ?string $description,
        public ?int $estimatedMinutes,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            basePrice: (float) $data['base_price'],
            description: $data['description'] ?? null,
            estimatedMinutes: isset($data['estimated_minutes']) ? (int) $data['estimated_minutes'] : null,
        );
    }
}
