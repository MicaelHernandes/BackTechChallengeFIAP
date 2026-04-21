<?php

namespace Domain\Inventory\Domain\Enums;

use Domain\Core\Domain\Exceptions\DomainException;

enum PartRequestStatus: string
{
    case Received           = 'received';
    case Reserved           = 'reserved';
    case OutOfStock         = 'out_of_stock';
    case Purchasing         = 'purchasing';
    case AvailableForPickup = 'available_for_pickup';
    case PickedUp           = 'picked_up';
    case Finalized          = 'finalized';

    public function label(): string
    {
        return match($this) {
            self::Received           => 'Recebida',
            self::Reserved           => 'Reservada',
            self::OutOfStock         => 'Sem Estoque',
            self::Purchasing         => 'Compra Solicitada',
            self::AvailableForPickup => 'Disponível para Retirada',
            self::PickedUp           => 'Retirada pelo Mecânico',
            self::Finalized          => 'Finalizada',
        };
    }

    /** @return array<PartRequestStatus> */
    public function allowedTransitions(): array
    {
        return match($this) {
            self::Received           => [self::Reserved, self::OutOfStock],
            self::Reserved           => [self::PickedUp],
            self::OutOfStock         => [self::Purchasing],
            self::Purchasing         => [self::AvailableForPickup],
            self::AvailableForPickup => [self::PickedUp],
            self::PickedUp           => [self::Finalized],
            self::Finalized          => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    public function guardTransitionTo(self $target): void
    {
        if (!$this->canTransitionTo($target)) {
            throw DomainException::because(
                "Transição inválida de '{$this->label()}' para '{$target->label()}'."
            );
        }
    }
}
