<?php

namespace Domain\Catalog\Application\UseCases\Service;

use Domain\Catalog\Application\DTOs\CreateServiceDTO;
use Domain\Catalog\Domain\Entities\Service;
use Domain\Catalog\Domain\Repositories\ServiceRepositoryInterface;

class CreateServiceUseCase
{
    public function __construct(private readonly ServiceRepositoryInterface $repository) {}

    public function execute(CreateServiceDTO $dto): Service
    {
        return $this->repository->save(Service::create(
            name: $dto->name,
            basePrice: $dto->basePrice,
            description: $dto->description,
            estimatedMinutes: $dto->estimatedMinutes,
        ));
    }
}
