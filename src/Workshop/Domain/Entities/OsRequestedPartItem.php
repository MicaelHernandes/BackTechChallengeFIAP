<?php

namespace Domain\Workshop\Domain\Entities;

/**
 * Item de peça solicitado pelo cliente/atendente na abertura da OS.
 * Sem preço: é apenas um registro informativo do que foi pedido, distinto do
 * orçamento oficial (OsPartItem), que só é gerado na etapa de diagnóstico.
 */
class OsRequestedPartItem
{
    public function __construct(
        private readonly ?int $id,
        private readonly int $partId,
        private readonly string $partName,
        private readonly int $quantity,
    ) {}

    public static function create(int $partId, string $partName, int $quantity): self
    {
        return new self(null, $partId, $partName, $quantity);
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

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'part_id' => $this->partId,
            'part_name' => $this->partName,
            'quantity' => $this->quantity,
        ];
    }
}
