<?php

namespace Domain\Catalog\Application\UseCases\Part;

use Domain\Catalog\Domain\Entities\Part;
use Domain\Catalog\Domain\Repositories\PartRepositoryInterface;
use Domain\Core\Domain\Exceptions\NotFoundException;

class GetPartUseCase
{
    public function __construct(private readonly PartRepositoryInterface $repository) {}

    public function execute(int $id): Part
    {
        return $this->repository->findById($id)
            ?? throw NotFoundException::forEntity('Peça', $id);
    }
}
