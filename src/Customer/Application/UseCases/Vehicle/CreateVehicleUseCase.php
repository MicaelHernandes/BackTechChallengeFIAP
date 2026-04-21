<?php

namespace Domain\Customer\Application\UseCases\Vehicle;

use Domain\Core\Domain\Exceptions\DomainException;
use Domain\Core\Domain\Exceptions\NotFoundException;
use Domain\Customer\Application\DTOs\CreateVehicleDTO;
use Domain\Customer\Domain\Entities\Vehicle;
use Domain\Customer\Domain\Repositories\CustomerRepositoryInterface;
use Domain\Customer\Domain\Repositories\VehicleRepositoryInterface;

class CreateVehicleUseCase
{
    public function __construct(
        private readonly VehicleRepositoryInterface $vehicleRepository,
        private readonly CustomerRepositoryInterface $customerRepository,
    ) {}

    public function execute(CreateVehicleDTO $dto): Vehicle
    {
        if ($this->customerRepository->findById($dto->customerId) === null) {
            throw NotFoundException::forEntity('Cliente', $dto->customerId);
        }

        if ($this->vehicleRepository->findByPlate($dto->plate) !== null) {
            throw DomainException::because("Já existe um veículo com a placa '{$dto->plate}'.");
        }

        $vehicle = Vehicle::create(
            plate: $dto->plate,
            brand: $dto->brand,
            model: $dto->model,
            year: $dto->year,
            color: $dto->color,
            customerId: $dto->customerId,
        );

        return $this->vehicleRepository->save($vehicle);
    }
}
