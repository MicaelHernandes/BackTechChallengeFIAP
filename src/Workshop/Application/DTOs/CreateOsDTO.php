<?php

namespace Domain\Workshop\Application\DTOs;

final readonly class CreateOsDTO
{
    /**
     * @param  array<array{service_id:int, quantity:int}>  $requestedServices
     * @param  array<array{part_id:int, quantity:int}>  $requestedParts
     */
    public function __construct(
        public int $customerId,
        public int $vehicleId,
        public string $complaint,
        public ?int $mechanicUserId,
        public array $requestedServices = [],
        public array $requestedParts = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            customerId: (int) $data['customer_id'],
            vehicleId: (int) $data['vehicle_id'],
            complaint: $data['complaint'],
            mechanicUserId: isset($data['mechanic_user_id']) ? (int) $data['mechanic_user_id'] : null,
            requestedServices: $data['services'] ?? [],
            requestedParts: $data['parts'] ?? [],
        );
    }
}
