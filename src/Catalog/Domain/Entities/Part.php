<?php

namespace Domain\Catalog\Domain\Entities;

use Domain\Core\Domain\Exceptions\DomainException;

class Part
{
    public function __construct(
        private readonly ?int $id,
        private string $code,
        private string $name,
        private ?string $description,
        private float $unitPrice,
        private int $stockQuantity,
        private int $minimumStock,
        private string $unit,
    ) {}

    public static function create(
        string $code,
        string $name,
        float $unitPrice,
        ?string $description = null,
        int $stockQuantity = 0,
        int $minimumStock = 0,
        string $unit = 'un',
    ): self {
        return new self(
            id: null,
            code: $code,
            name: $name,
            description: $description,
            unitPrice: $unitPrice,
            stockQuantity: $stockQuantity,
            minimumStock: $minimumStock,
            unit: $unit,
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getUnitPrice(): float
    {
        return $this->unitPrice;
    }

    public function getStockQuantity(): int
    {
        return $this->stockQuantity;
    }

    public function getMinimumStock(): int
    {
        return $this->minimumStock;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function hasStock(int $quantity): bool
    {
        return $this->stockQuantity >= $quantity;
    }

    public function decrementStock(int $quantity): void
    {
        if ($this->stockQuantity < $quantity) {
            throw DomainException::because(
                "Estoque insuficiente para '{$this->name}': disponível {$this->stockQuantity}, solicitado {$quantity}."
            );
        }
        $this->stockQuantity -= $quantity;
    }

    public function incrementStock(int $quantity): void
    {
        if ($quantity <= 0) {
            throw DomainException::because('A quantidade a adicionar deve ser maior que zero.');
        }
        $this->stockQuantity += $quantity;
    }

    public function updateDetails(
        string $name,
        float $unitPrice,
        ?string $description,
        int $minimumStock,
        string $unit,
    ): void {
        $this->name         = $name;
        $this->unitPrice    = $unitPrice;
        $this->description  = $description;
        $this->minimumStock = $minimumStock;
        $this->unit         = $unit;
    }

    public function toArray(): array
    {
        return [
            'id'             => $this->id,
            'code'           => $this->code,
            'name'           => $this->name,
            'description'    => $this->description,
            'unit_price'     => $this->unitPrice,
            'stock_quantity' => $this->stockQuantity,
            'minimum_stock'  => $this->minimumStock,
            'unit'           => $this->unit,
        ];
    }
}
