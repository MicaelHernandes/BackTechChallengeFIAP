<?php

namespace Domain\Workshop\Application\UseCases;

use Domain\Core\Domain\Exceptions\NotFoundException;
use Domain\Customer\Domain\Repositories\CustomerRepositoryInterface;
use Domain\Customer\Domain\Repositories\VehicleRepositoryInterface;
use Domain\Workshop\Application\Concerns\DispatchesOsEvents;
use Domain\Workshop\Application\DTOs\CreateOsDTO;
use Domain\Workshop\Domain\Entities\OrderService;
use Domain\Workshop\Domain\Enums\OsStatus;
use Domain\Workshop\Domain\Repositories\OrderServiceRepositoryInterface;

/**
 * AG: Criação da OS
 * Recebe Cliente, Veículo e Reclamação → Status: CREATED
 * Dispara OsStatusUpdatedEvent para notificar o cliente.
 */
class CreateOsUseCase
{
    use DispatchesOsEvents;

    public function __construct(
        private readonly OrderServiceRepositoryInterface $osRepository,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly VehicleRepositoryInterface $vehicleRepository,
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
        );

        $saved = $this->osRepository->save($os);

        $this->dispatchOsStatusUpdated($saved, OsStatus::Created, $this->customerRepository);

        return $saved;
    }
}
