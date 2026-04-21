<?php

namespace Domain\Workshop\Domain\Entities;

class Budget
{
    public function __construct(
        private readonly ?int $id,
        private readonly int $osId,
        private float $totalServices,
        private float $totalParts,
        private float $totalAmount,
        private ?string $notes,
    ) {}

    public static function calculate(
        int $osId,
        array $serviceItems,
        array $partItems,
        ?string $notes = null,
        ?int $existingId = null,
    ): self {
        $totalServices = array_reduce(
            $serviceItems,
            fn (float $carry, OsServiceItem $item) => $carry + $item->getTotal(),
            0.0
        );

        $totalParts = array_reduce(
            $partItems,
            fn (float $carry, OsPartItem $item) => $carry + $item->getTotal(),
            0.0
        );

        return new self(
            id: $existingId,
            osId: $osId,
            totalServices: round($totalServices, 2),
            totalParts: round($totalParts, 2),
            totalAmount: round($totalServices + $totalParts, 2),
            notes: $notes,
        );
    }

    public function getId(): ?int { return $this->id; }
    public function getOsId(): int { return $this->osId; }
    public function getTotalServices(): float { return $this->totalServices; }
    public function getTotalParts(): float { return $this->totalParts; }
    public function getTotalAmount(): float { return $this->totalAmount; }
    public function getNotes(): ?string { return $this->notes; }

    public function toArray(): array
    {
        return [
            'id'             => $this->id,
            'total_services' => $this->totalServices,
            'total_parts'    => $this->totalParts,
            'total_amount'   => $this->totalAmount,
            'notes'          => $this->notes,
        ];
    }
}
