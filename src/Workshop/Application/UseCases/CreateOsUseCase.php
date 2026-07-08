<?php

namespace Domain\Workshop\Application\UseCases;

use Domain\Catalog\Domain\Repositories\PartRepositoryInterface;
use Domain\Catalog\Domain\Repositories\ServiceRepositoryInterface;
use Domain\Core\Domain\Exceptions\NotFoundException;
use Domain\Customer\Domain\Repositories\CustomerRepositoryInterface;
use Domain\Customer\Domain\Repositories\VehicleRepositoryInterface;
use Domain\Workshop\Application\Concerns\DispatchesOsEvents;
use Domain\Workshop\Application\DTOs\CreateOsDTO;
use Domain\Workshop\Domain\Entities\OrderService;
use Domain\Workshop\Domain\Entities\OsRequestedPartItem;
use Domain\Workshop\Domain\Entities\OsRequestedServiceItem;
use Domain\Workshop\Domain\Enums\OsStatus;
use Domain\Workshop\Domain\Repositories\OrderServiceRepositoryInterface;

/**
 * AG: Criação da OS
 * Recebe Cliente, Veículo e Reclamação → Status: CREATED
 * Serviços/peças informados na abertura são registrados apenas como itens
 * solicitados (sem preço) — o orçamento oficial ainda é gerado na etapa de
 * diagnóstico via GenerateBudgetUseCase.
 * Dispara OsStatusUpdatedEvent para notificar o cliente.
 */
class CreateOsUseCase
{
    use DispatchesOsEvents;

    public function __construct(
        private readonly OrderServiceRepositoryInterface $osRepository,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly VehicleRepositoryInterface $vehicleRepository,
        private readonly ServiceRepositoryInterface $serviceRepository,
        private readonly PartRepositoryInterface $partRepository,
    ) {}

    public function execute(CreateOsDTO $dto): OrderService
    {
        if ($this->customerRepository->findById($dto->customerId) === null) {
            throw NotFoundException::forEntity('Cliente', $dto->customerId);
        }

        $vehicle = $this->vehicleRepository->findById($dto->vehicleId)
            ?? throw NotFoundException::forEntity('Veículo', $dto->vehicleId);

        if ($vehicle->getCustomerId() !== $dto->customerId) {
            throw NotFoundException::forEntity('Veículo do cliente', $dto->vehicleId);
        }

        $os = OrderService::create(
            customerId: $dto->customerId,
            vehicleId: $dto->vehicleId,
            complaint: $dto->complaint,
            mechanicUserId: $dto->mechanicUserId,
            requestedServiceItems: $this->buildRequestedServiceItems($dto->requestedServices),
            requestedPartItems: $this->buildRequestedPartItems($dto->requestedParts),
        );

        $saved = $this->osRepository->save($os);

        $this->dispatchOsStatusUpdated($saved, OsStatus::Created, $this->customerRepository);

        return $saved;
    }

    /** @return OsRequestedServiceItem[] */
    private function buildRequestedServiceItems(array $requestedServices): array
    {
        $items = [];

        foreach ($requestedServices as $item) {
            $service = $this->serviceRepository->findById((int) $item['service_id'])
                ?? throw NotFoundException::forEntity('Serviço', $item['service_id']);

            $items[] = OsRequestedServiceItem::create(
                serviceId: $service->getId(),
                serviceName: $service->getName(),
                quantity: (int) $item['quantity'],
            );
        }

        return $items;
    }

    /** @return OsRequestedPartItem[] */
    private function buildRequestedPartItems(array $requestedParts): array
    {
        $items = [];

        foreach ($requestedParts as $item) {
            $part = $this->partRepository->findById((int) $item['part_id'])
                ?? throw NotFoundException::forEntity('Peça', $item['part_id']);

            $items[] = OsRequestedPartItem::create(
                partId: $part->getId(),
                partName: $part->getName(),
                quantity: (int) $item['quantity'],
            );
        }

        return $items;
    }
}
