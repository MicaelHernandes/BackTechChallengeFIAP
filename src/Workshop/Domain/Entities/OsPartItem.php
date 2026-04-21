<?php

namespace Domain\Workshop\Domain\Entities;

class OsPartItem
{
    public function __construct(
        private readonly ?int $id,
        private readonly int $partId,
        private readonly string $partName,
        private readonly int $quantity,
        private readonly float $unitPrice,
    ) {}

    public static function create(int $partId, string $partName, int $quantity, float $unitPrice): self
    {
        return new self(null, $partId, $partName, $quantity, $unitPrice);
    }

    public function getId(): ?int { return $this->id; }
    public function getPartId(): int { return $this->partId; }
    public function getPartName(): string { return $this->partName; }
    public function getQuantity(): int { return $this->quantity; }
    public function getUnitPrice(): float { return $this->unitPrice; }
    public function getTotal(): float { return round($this->quantity * $this->unitPrice, 2); }

    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'part_id'    => $this->partId,
            'part_name'  => $this->partName,
            'quantity'   => $this->quantity,
            'unit_price' => $this->unitPrice,
            'total'      => $this->getTotal(),
        ];
    }
}
