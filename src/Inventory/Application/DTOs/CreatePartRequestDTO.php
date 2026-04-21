<?php

namespace Domain\Inventory\Application\DTOs;

final readonly class CreatePartRequestDTO
{
    /** @param array<array{part_id:int, quantity:int}> $items */
    public function __construct(
        public int $requestedByUserId,
        public ?int $osId,
        public ?string $notes,
        public array $items,
    ) {}

    public static function fromArray(int $userId, array $data): self
    {
        return new self(
            requestedByUserId: $userId,
            osId: $data['os_id'] ?? null,
            notes: $data['notes'] ?? null,
            items: $data['items'],
        );
    }
}
