<?php

namespace Domain\Core\Domain\ValueObjects;

abstract class ValueObject
{
    abstract public function value(): mixed;

    public function equals(self $other): bool
    {
        return get_class($this) === get_class($other)
            && $this->value() === $other->value();
    }

    public function __toString(): string
    {
        return (string) $this->value();
    }
}
