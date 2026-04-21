<?php

namespace Domain\Workshop\Domain\Entities;

class OsServiceItem
{
    public function __construct(
        private readonly ?int $id,
        private readonly int $serviceId,
        private readonly string $serviceName,
        private readonly int $quantity,
        private readonly float $unitPrice,
    ) {}

    public static function create(int $serviceId, string $serviceName, int $quantity, float $unitPrice): self
    {
        return new self(null, $serviceId, $serviceName, $quantity, $unitPrice);
    }

    public function getId(): ?int { return $this->id; }
    public function getServiceId(): int { return $this->serviceId; }
    public function getServiceName(): string { return $this->serviceName; }
    public function getQuantity(): int { return $this->quantity; }
    public function getUnitPrice(): float { return $this->unitPrice; }
    public function getTotal(): float { return round($this->quantity * $this->unitPrice, 2); }

    public function toArray(): array
    {
        return [
            'id'           => $this->id,
            'service_id'   => $this->serviceId,
            'service_name' => $this->serviceName,
            'quantity'     => $this->quantity,
            'unit_price'   => $this->unitPrice,
            'total'        => $this->getTotal(),
        ];
    }
}
