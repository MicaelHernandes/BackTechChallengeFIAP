<?php

namespace Domain\Customer\Application\UseCases\Vehicle;

use Domain\Core\Domain\Exceptions\NotFoundException;
use Domain\Customer\Domain\Repositories\VehicleRepositoryInterface;

class DeleteVehicleUseCase
{
    public function __construct(
        private readonly VehicleRepositoryInterface $repository,
    ) {}

    public function execute(int $id): void
    {
        if ($this->repository->findById($id) === null) {
            throw NotFoundException::forEntity('Veículo', $id);
        }

        $this->repository->delete($id);
    }
}
