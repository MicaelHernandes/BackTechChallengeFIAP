<?php

namespace Domain\Catalog\Application\UseCases\Service;

use Domain\Catalog\Application\DTOs\UpdateServiceDTO;
use Domain\Catalog\Domain\Entities\Service;
use Domain\Catalog\Domain\Repositories\ServiceRepositoryInterface;
use Domain\Core\Domain\Exceptions\NotFoundException;

class UpdateServiceUseCase
{
    public function __construct(private readonly ServiceRepositoryInterface $repository) {}

    public function execute(UpdateServiceDTO $dto): Service
    {
        $service = $this->repository->findById($dto->id)
            ?? throw NotFoundException::forEntity('Serviço', $dto->id);

        $service->updateDetails($dto->name, $dto->basePrice, $dto->description, $dto->estimatedMinutes);

        return $this->repository->save($service);
    }
}
