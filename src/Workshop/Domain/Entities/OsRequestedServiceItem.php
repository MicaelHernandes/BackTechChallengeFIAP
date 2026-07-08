<?php

namespace Domain\Workshop\Domain\Entities;

/**
 * Item de serviço solicitado pelo cliente/atendente na abertura da OS.
 * Sem preço: é apenas um registro informativo do que foi pedido, distinto do
 * orçamento oficial (OsServiceItem), que só é gerado na etapa de diagnóstico.
 */
class OsRequestedServiceItem
{
    public function __construct(
        private readonly ?int $id,
        private readonly int $serviceId,
        private readonly string $serviceName,
        private readonly int $quantity,
    ) {}

    public static function create(int $serviceId, string $serviceName, int $quantity): self
    {
        return new self(null, $serviceId, $serviceName, $quantity);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getServiceId(): int
    {
        return $this->serviceId;
    }

    public function getServiceName(): string
    {
        return $this->serviceName;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'service_id' => $this->serviceId,
            'service_name' => $this->serviceName,
            'quantity' => $this->quantity,
        ];
    }
}
