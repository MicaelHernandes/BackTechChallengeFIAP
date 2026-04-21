<?php

namespace Domain\Customer\Application\DTOs;

final readonly class UpdateCustomerDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public ?string $phone,
        public ?string $address,
    ) {}

    public static function fromArray(int $id, array $data): self
    {
        return new self(
            id: $id,
            name: $data['name'],
            email: $data['email'],
            phone: $data['phone'] ?? null,
            address: $data['address'] ?? null,
        );
    }
}
