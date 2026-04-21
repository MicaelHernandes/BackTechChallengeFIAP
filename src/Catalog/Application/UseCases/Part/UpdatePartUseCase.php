<?php

namespace Domain\Catalog\Application\UseCases\Part;

use Domain\Catalog\Application\DTOs\UpdatePartDTO;
use Domain\Catalog\Domain\Entities\Part;
use Domain\Catalog\Domain\Repositories\PartRepositoryInterface;
use Domain\Core\Domain\Exceptions\NotFoundException;

class UpdatePartUseCase
{
    public function __construct(private readonly PartRepositoryInterface $repository) {}

    public function execute(UpdatePartDTO $dto): Part
    {
        $part = $this->repository->findById($dto->id)
            ?? throw NotFoundException::forEntity('Peça', $dto->id);

        $part->updateDetails(
            name: $dto->name,
            unitPrice: $dto->unitPrice,
            description: $dto->description,
            minimumStock: $dto->minimumStock,
            unit: $dto->unit,
        );

        return $this->repository->save($part);
    }
}
