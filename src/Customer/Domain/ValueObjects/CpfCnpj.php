<?php

namespace Domain\Customer\Domain\ValueObjects;

use Domain\Core\Domain\Exceptions\DomainException;
use Domain\Core\Domain\ValueObjects\ValueObject;

final class CpfCnpj extends ValueObject
{
    private readonly string $document;

    public function __construct(string $document)
    {
        $clean = preg_replace('/\D/', '', $document);

        if (strlen($clean) === 11) {
            $this->guardValidCpf($clean);
        } elseif (strlen($clean) === 14) {
            $this->guardValidCnpj($clean);
        } else {
            throw DomainException::because('Documento inválido: deve ser um CPF (11 dígitos) ou CNPJ (14 dígitos).');
        }

        $this->document = $clean;
    }

    public function value(): string
    {
        return $this->document;
    }

    public function isCpf(): bool
    {
        return strlen($this->document) === 11;
    }

    public function isCnpj(): bool
    {
        return strlen($this->document) === 14;
    }

    public function formatted(): string
    {
        if ($this->isCpf()) {
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $this->document);
        }

        return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $this->document);
    }

    private function guardValidCpf(string $cpf): void
    {
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            throw DomainException::because('CPF inválido: todos os dígitos são iguais.');
        }

        for ($t = 9; $t < 11; $t++) {
            $sum = 0;
            for ($i = 0; $i < $t; $i++) {
                $sum += (int) $cpf[$i] * ($t + 1 - $i);
            }
            $remainder = ((10 * $sum) % 11) % 10;
            if ((int) $cpf[$t] !== $remainder) {
                throw DomainException::because('CPF inválido: dígitos verificadores incorretos.');
            }
        }
    }

    private function guardValidCnpj(string $cnpj): void
    {
        if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
            throw DomainException::because('CNPJ inválido: todos os dígitos são iguais.');
        }

        $weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        $this->guardCnpjDigit($cnpj, $weights1, 12);
        $this->guardCnpjDigit($cnpj, $weights2, 13);
    }

    private function guardCnpjDigit(string $cnpj, array $weights, int $position): void
    {
        $sum = 0;
        for ($i = 0; $i < $position; $i++) {
            $sum += (int) $cnpj[$i] * $weights[$i];
        }
        $remainder = $sum % 11 < 2 ? 0 : 11 - ($sum % 11);
        if ((int) $cnpj[$position] !== $remainder) {
            throw DomainException::because('CNPJ inválido: dígitos verificadores incorretos.');
        }
    }
}
