<?php

namespace Domain\Customer\Domain\Entities;

use Domain\Customer\Domain\ValueObjects\CpfCnpj;

class Customer
{
    public function __construct(
        private readonly ?int $id,
        private string $name,
        private CpfCnpj $document,
        private string $email,
        private ?string $phone,
        private ?string $address,
    ) {}

    public static function create(
        string $name,
        string $document,
        string $email,
        ?string $phone = null,
        ?string $address = null,
    ): self {
        return new self(
            id: null,
            name: $name,
            document: new CpfCnpj($document),
            email: $email,
            phone: $phone,
            address: $address,
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDocument(): CpfCnpj
    {
        return $this->document;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function updateDetails(
        string $name,
        string $email,
        ?string $phone,
        ?string $address,
    ): void {
        $this->name    = $name;
        $this->email   = $email;
        $this->phone   = $phone;
        $this->address = $address;
    }

    public function toArray(): array
    {
        return [
            'id'       => $this->id,
            'name'     => $this->name,
            'document' => $this->document->value(),
            'email'    => $this->email,
            'phone'    => $this->phone,
            'address'  => $this->address,
        ];
    }
}
