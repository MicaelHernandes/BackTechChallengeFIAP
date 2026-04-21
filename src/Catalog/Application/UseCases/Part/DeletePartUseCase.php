<?php

namespace Domain\Catalog\Application\UseCases\Part;

use Domain\Catalog\Domain\Repositories\PartRepositoryInterface;
use Domain\Core\Domain\Exceptions\NotFoundException;

class DeletePartUseCase
{
    public function __construct(private readonly PartRepositoryInterface $repository) {}

    public function execute(int $id): void
    {
        if ($this->repository->findById($id) === null) {
            throw NotFoundException::forEntity('Peça', $id);
        }
        $this->repository->delete($id);
    }
}
