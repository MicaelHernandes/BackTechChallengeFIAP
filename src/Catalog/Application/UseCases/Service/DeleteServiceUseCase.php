<?php

namespace Domain\Catalog\Application\UseCases\Service;

use Domain\Catalog\Domain\Repositories\ServiceRepositoryInterface;
use Domain\Core\Domain\Exceptions\NotFoundException;

class DeleteServiceUseCase
{
    public function __construct(private readonly ServiceRepositoryInterface $repository) {}

    public function execute(int $id): void
    {
        if ($this->repository->findById($id) === null) {
            throw NotFoundException::forEntity('Serviço', $id);
        }
        $this->repository->delete($id);
    }
}
