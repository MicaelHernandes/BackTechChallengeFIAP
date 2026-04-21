<?php

namespace Domain\Core\Domain\Exceptions;

use RuntimeException;

class DomainException extends RuntimeException
{
    public static function because(string $reason): static
    {
        return new static($reason);
    }
}
