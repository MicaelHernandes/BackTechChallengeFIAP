<?php

namespace Domain\Workshop\Application\DTOs;

final readonly class CreateOsDTO
{
    public function __construct(
        public int $customerId,
        public int $vehicleId,
        public string $complaint,
        public ?int $mechanicUserId,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            customerId: (int) $data['customer_id'],
            vehicleId: (int) $data['vehicle_id'],
            complaint: $data['complaint'],
            mechanicUserId: isset($data['mechanic_user_id']) ? (int) $data['mechanic_user_id'] : null,
        );
    }
}
