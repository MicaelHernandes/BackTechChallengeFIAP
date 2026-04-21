<?php

namespace Domain\Customer\Application\DTOs;

final readonly class CreateVehicleDTO
{
    public function __construct(
        public string $plate,
        public string $brand,
        public string $model,
        public int $year,
        public string $color,
        public int $customerId,
    ) {}

    public static function fromArray(int $customerId, array $data): self
    {
        return new self(
            plate: $data['plate'],
            brand: $data['brand'],
            model: $data['model'],
            year: (int) $data['year'],
            color: $data['color'],
            customerId: $customerId,
        );
    }
}
