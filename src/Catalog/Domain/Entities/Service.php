<?php

namespace Domain\Catalog\Domain\Entities;

class Service
{
    public function __construct(
        private readonly ?int $id,
        private string $name,
        private ?string $description,
        private float $basePrice,
        private ?int $estimatedMinutes,
        private bool $isActive,
    ) {}

    public static function create(
        string $name,
        float $basePrice,
        ?string $description = null,
        ?int $estimatedMinutes = null,
    ): self {
        return new self(
            id: null,
            name: $name,
            description: $description,
            basePrice: $basePrice,
            estimatedMinutes: $estimatedMinutes,
            isActive: true,
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getBasePrice(): float
    {
        return $this->basePrice;
    }

    public function getEstimatedMinutes(): ?int
    {
        return $this->estimatedMinutes;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function activate(): void
    {
        $this->isActive = true;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    public function updateDetails(
        string $name,
        float $basePrice,
        ?string $description,
        ?int $estimatedMinutes,
    ): void {
        $this->name             = $name;
        $this->basePrice        = $basePrice;
        $this->description      = $description;
        $this->estimatedMinutes = $estimatedMinutes;
    }

    public function toArray(): array
    {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'description'       => $this->description,
            'base_price'        => $this->basePrice,
            'estimated_minutes' => $this->estimatedMinutes,
            'is_active'         => $this->isActive,
        ];
    }
}
