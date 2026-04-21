<?php

namespace Domain\Customer\Application\UseCases\Vehicle;

use Domain\Core\Domain\Exceptions\NotFoundException;
use Domain\Customer\Domain\Entities\Vehicle;
use Domain\Customer\Domain\Repositories\VehicleRepositoryInterface;

class GetVehicleUseCase
{
    public function __construct(
        private readonly VehicleRepositoryInterface $repository,
    ) {}

    public function execute(int $id): Vehicle
    {
        $vehicle = $this->repository->findById($id);

        if ($vehicle === null) {
            throw NotFoundException::forEntity('Veículo', $id);
        }

        return $vehicle;
    }
}
