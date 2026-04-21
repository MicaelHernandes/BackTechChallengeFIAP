<?php

namespace Domain\Catalog\Application\UseCases\Part;

use Domain\Catalog\Application\DTOs\CreatePartDTO;
use Domain\Catalog\Domain\Entities\Part;
use Domain\Catalog\Domain\Repositories\PartRepositoryInterface;
use Domain\Core\Domain\Exceptions\DomainException;

class CreatePartUseCase
{
    public function __construct(private readonly PartRepositoryInterface $repository) {}

    public function execute(CreatePartDTO $dto): Part
    {
        if ($this->repository->findByCode($dto->code) !== null) {
            throw DomainException::because("Já existe uma peça com o código '{$dto->code}'.");
        }

        return $this->repository->save(Part::create(
            code: $dto->code,
            name: $dto->name,
            unitPrice: $dto->unitPrice,
            description: $dto->description,
            stockQuantity: $dto->stockQuantity,
            minimumStock: $dto->minimumStock,
            unit: $dto->unit,
        ));
    }
}
