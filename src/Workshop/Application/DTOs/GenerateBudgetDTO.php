<?php

namespace Domain\Workshop\Application\DTOs;

final readonly class GenerateBudgetDTO
{
    /**
     * @param array<array{service_id:int, quantity:int}> $services
     * @param array<array{part_id:int, quantity:int}>    $parts
     */
    public function __construct(
        public int $osId,
        public array $services,
        public array $parts,
        public ?string $notes,
    ) {}

    public static function fromArray(int $osId, array $data): self
    {
        return new self(
            osId: $osId,
            services: $data['services'] ?? [],
            parts: $data['parts'] ?? [],
            notes: $data['notes'] ?? null,
        );
    }
}
