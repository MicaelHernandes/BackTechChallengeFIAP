<?php

namespace Domain\Customer\Domain\ValueObjects;

use Domain\Core\Domain\Exceptions\DomainException;
use Domain\Core\Domain\ValueObjects\ValueObject;

final class LicensePlate extends ValueObject
{
    /** Padrão Mercosul: AAA1A11 */
    private const MERCOSUL = '/^[A-Z]{3}[0-9][A-Z][0-9]{2}$/';

    /** Padrão antigo: AAA-1111 */
    private const OLD_FORMAT = '/^[A-Z]{3}[0-9]{4}$/';

    private readonly string $plate;

    public function __construct(string $plate)
    {
        $normalized = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $plate));

        if (!preg_match(self::MERCOSUL, $normalized) && !preg_match(self::OLD_FORMAT, $normalized)) {
            throw DomainException::because(
                "Placa inválida '{$plate}': deve seguir o padrão antigo (AAA-1111) ou Mercosul (AAA1A11)."
            );
        }

        $this->plate = $normalized;
    }

    public function value(): string
    {
        return $this->plate;
    }

    public function isMercosul(): bool
    {
        return (bool) preg_match(self::MERCOSUL, $this->plate);
    }

    public function formatted(): string
    {
        if ($this->isMercosul()) {
            return substr($this->plate, 0, 3) . '-' . substr($this->plate, 3);
        }

        return substr($this->plate, 0, 3) . '-' . substr($this->plate, 3);
    }
}
