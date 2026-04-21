<?php

use Domain\Core\Domain\Exceptions\DomainException;
use Domain\Inventory\Domain\Enums\PartRequestStatus;

describe('PartRequestStatus state machine', function () {

    it('allows RECEIVED → RESERVED', function () {
        expect(PartRequestStatus::Received->canTransitionTo(PartRequestStatus::Reserved))->toBeTrue();
    });

    it('allows RECEIVED → OUT_OF_STOCK', function () {
        expect(PartRequestStatus::Received->canTransitionTo(PartRequestStatus::OutOfStock))->toBeTrue();
    });

    it('allows OUT_OF_STOCK → PURCHASING', function () {
        expect(PartRequestStatus::OutOfStock->canTransitionTo(PartRequestStatus::Purchasing))->toBeTrue();
    });

    it('allows PURCHASING → AVAILABLE_FOR_PICKUP', function () {
        expect(PartRequestStatus::Purchasing->canTransitionTo(PartRequestStatus::AvailableForPickup))->toBeTrue();
    });

    it('allows RESERVED → PICKED_UP', function () {
        expect(PartRequestStatus::Reserved->canTransitionTo(PartRequestStatus::PickedUp))->toBeTrue();
    });

    it('allows AVAILABLE_FOR_PICKUP → PICKED_UP', function () {
        expect(PartRequestStatus::AvailableForPickup->canTransitionTo(PartRequestStatus::PickedUp))->toBeTrue();
    });

    it('allows PICKED_UP → FINALIZED', function () {
        expect(PartRequestStatus::PickedUp->canTransitionTo(PartRequestStatus::Finalized))->toBeTrue();
    });

    it('rejects RECEIVED → PICKED_UP (skip)', function () {
        expect(PartRequestStatus::Received->canTransitionTo(PartRequestStatus::PickedUp))->toBeFalse();
    });

    it('rejects FINALIZED → any status', function () {
        foreach (PartRequestStatus::cases() as $target) {
            expect(PartRequestStatus::Finalized->canTransitionTo($target))->toBeFalse();
        }
    });

    it('rejects RESERVED → OUT_OF_STOCK', function () {
        expect(PartRequestStatus::Reserved->canTransitionTo(PartRequestStatus::OutOfStock))->toBeFalse();
    });

    it('throws DomainException on invalid transition via guardTransitionTo', function () {
        expect(fn () => PartRequestStatus::Finalized->guardTransitionTo(PartRequestStatus::Received))
            ->toThrow(DomainException::class, 'Transição inválida');
    });

    it('has correct labels', function () {
        expect(PartRequestStatus::Received->label())->toBe('Recebida');
        expect(PartRequestStatus::OutOfStock->label())->toBe('Sem Estoque');
        expect(PartRequestStatus::Finalized->label())->toBe('Finalizada');
    });
});
