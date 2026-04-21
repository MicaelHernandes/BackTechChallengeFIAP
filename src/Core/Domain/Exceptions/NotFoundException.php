<?php

namespace Domain\Core\Domain\Exceptions;

class NotFoundException extends DomainException
{
    public static function forEntity(string $entity, int|string $id): static
    {
        return new static("{$entity} com ID '{$id}' não encontrado.");
    }
}
