<?php

namespace Domain\Catalog\Application\UseCases\Service;

use Domain\Catalog\Domain\Entities\Service;
use Domain\Catalog\Domain\Repositories\ServiceRepositoryInterface;
use Domain\Core\Domain\Exceptions\NotFoundException;

class GetServiceUseCase
{
    public function __construct(private readonly ServiceRepositoryInterface $repository) {}

    public function execute(int $id): Service
    {
        return $this->repository->findById($id)
            ?? throw NotFoundException::forEntity('Serviço', $id);
    }
}
