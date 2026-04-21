<?php

namespace Domain\Customer\Application\UseCases\Vehicle;

use Domain\Core\Domain\Exceptions\DomainException;
use Domain\Core\Domain\Exceptions\NotFoundException;
use Domain\Customer\Application\DTOs\UpdateVehicleDTO;
use Domain\Customer\Domain\Entities\Vehicle;
use Domain\Customer\Domain\Repositories\VehicleRepositoryInterface;

class UpdateVehicleUseCase
{
    public function __construct(
        private readonly VehicleRepositoryInterface $repository,
    ) {}

    public function execute(UpdateVehicleDTO $dto): Vehicle
    {
        $vehicle = $this->repository->findById($dto->id);

        if ($vehicle === null) {
            throw NotFoundException::forEntity('Veículo', $dto->id);
        }

        $existing = $this->repository->findByPlate($dto->plate);
        if ($existing !== null && $existing->getId() !== $dto->id) {
            throw DomainException::because("Já existe outro veículo com a placa '{$dto->plate}'.");
        }

        $vehicle->changePlate($dto->plate);
        $vehicle->updateDetails($dto->brand, $dto->model, $dto->year, $dto->color);

        return $this->repository->save($vehicle);
    }
}
