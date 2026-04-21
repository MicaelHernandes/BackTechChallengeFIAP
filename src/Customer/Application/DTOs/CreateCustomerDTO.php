<?php

namespace Domain\Customer\Application\DTOs;

final readonly class CreateCustomerDTO
{
    public function __construct(
        public string $name,
        public string $document,
        public string $email,
        public ?string $phone,
        public ?string $address,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            document: $data['document'],
            email: $data['email'],
            phone: $data['phone'] ?? null,
            address: $data['address'] ?? null,
        );
    }
}
