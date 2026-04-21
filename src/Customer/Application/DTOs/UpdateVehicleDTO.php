<?php

namespace Domain\Customer\Application\DTOs;

final readonly class UpdateVehicleDTO
{
    public function __construct(
        public int $id,
        public string $plate,
        public string $brand,
        public string $model,
        public int $year,
        public string $color,
    ) {}

    public static function fromArray(int $id, array $data): self
    {
        return new self(
            id: $id,
            plate: $data['plate'],
            brand: $data['brand'],
            model: $data['model'],
            year: (int) $data['year'],
            color: $data['color'],
        );
    }
}
