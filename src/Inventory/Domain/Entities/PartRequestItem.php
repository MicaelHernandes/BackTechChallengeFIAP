<?php

namespace Domain\Inventory\Domain\Entities;

class PartRequestItem
{
    public function __construct(
        private readonly ?int $id,
        private readonly int $partId,
        private readonly string $partName,
        private readonly int $quantityRequested,
        private int $quantityProvided,
    ) {}

    public static function create(int $partId, string $partName, int $quantityRequested): self
    {
        return new self(
            id: null,
            partId: $partId,
            partName: $partName,
            quantityRequested: $quantityRequested,
            quantityProvided: 0,
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPartId(): int
    {
        return $this->partId;
    }

    public function getPartName(): string
    {
        return $this->partName;
    }

    public function getQuantityRequested(): int
    {
        return $this->quantityRequested;
    }

    public function getQuantityProvided(): int
    {
        return $this->quantityProvided;
    }

    public function setQuantityProvided(int $quantity): void
    {
        $this->quantityProvided = $quantity;
    }

    public function toArray(): array
    {
        return [
            'id'                 => $this->id,
            'part_id'            => $this->partId,
            'part_name'          => $this->partName,
            'quantity_requested' => $this->quantityRequested,
            'quantity_provided'  => $this->quantityProvided,
        ];
    }
}
