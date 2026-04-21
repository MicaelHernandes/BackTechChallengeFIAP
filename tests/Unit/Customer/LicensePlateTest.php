<?php

use Domain\Core\Domain\Exceptions\DomainException;
use Domain\Customer\Domain\ValueObjects\LicensePlate;

describe('LicensePlate Value Object', function () {

    it('accepts old-format plate (AAA-1234)', function () {
        $vo = new LicensePlate('ABC-1234');
        expect($vo->value())->toBe('ABC1234');
        expect($vo->isMercosul())->toBeFalse();
    });

    it('accepts old-format plate without hyphen', function () {
        $vo = new LicensePlate('ABC1234');
        expect($vo->value())->toBe('ABC1234');
    });

    it('accepts Mercosul plate (AAA1A23)', function () {
        $vo = new LicensePlate('ABC1D23');
        expect($vo->value())->toBe('ABC1D23');
        expect($vo->isMercosul())->toBeTrue();
    });

    it('normalizes plate to uppercase', function () {
        $vo = new LicensePlate('abc1d23');
        expect($vo->value())->toBe('ABC1D23');
    });

    it('formats old plate with hyphen', function () {
        $vo = new LicensePlate('ABC1234');
        expect($vo->formatted())->toBe('ABC-1234');
    });

    it('formats Mercosul plate with hyphen', function () {
        $vo = new LicensePlate('ABC1D23');
        expect($vo->formatted())->toBe('ABC-1D23');
    });

    it('rejects plate with wrong format', function () {
        expect(fn () => new LicensePlate('12AB345'))
            ->toThrow(DomainException::class, 'Placa inválida');
    });

    it('rejects empty string', function () {
        expect(fn () => new LicensePlate(''))
            ->toThrow(DomainException::class);
    });

    it('two VOs with the same plate are equal', function () {
        $a = new LicensePlate('ABC-1234');
        $b = new LicensePlate('abc1234');
        expect($a->equals($b))->toBeTrue();
    });
});
