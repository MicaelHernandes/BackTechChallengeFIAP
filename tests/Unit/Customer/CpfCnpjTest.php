<?php

use Domain\Core\Domain\Exceptions\DomainException;
use Domain\Customer\Domain\ValueObjects\CpfCnpj;

describe('CpfCnpj Value Object', function () {

    describe('CPF validation', function () {
        it('accepts a valid CPF (digits only)', function () {
            $vo = new CpfCnpj('52998224725');
            expect($vo->value())->toBe('52998224725');
            expect($vo->isCpf())->toBeTrue();
            expect($vo->isCnpj())->toBeFalse();
        });

        it('accepts a valid CPF (formatted)', function () {
            $vo = new CpfCnpj('529.982.247-25');
            expect($vo->value())->toBe('52998224725');
        });

        it('formats CPF correctly', function () {
            $vo = new CpfCnpj('52998224725');
            expect($vo->formatted())->toBe('529.982.247-25');
        });

        it('rejects CPF with all identical digits', function () {
            expect(fn () => new CpfCnpj('11111111111'))
                ->toThrow(DomainException::class, 'todos os dígitos são iguais');
        });

        it('rejects CPF with wrong check digits', function () {
            expect(fn () => new CpfCnpj('12345678901'))
                ->toThrow(DomainException::class, 'dígitos verificadores incorretos');
        });

        it('rejects document with invalid length', function () {
            expect(fn () => new CpfCnpj('123'))
                ->toThrow(DomainException::class, 'deve ser um CPF');
        });
    });

    describe('CNPJ validation', function () {
        it('accepts a valid CNPJ (digits only)', function () {
            $vo = new CpfCnpj('11222333000181');
            expect($vo->value())->toBe('11222333000181');
            expect($vo->isCnpj())->toBeTrue();
            expect($vo->isCpf())->toBeFalse();
        });

        it('accepts a valid CNPJ (formatted)', function () {
            $vo = new CpfCnpj('11.222.333/0001-81');
            expect($vo->value())->toBe('11222333000181');
        });

        it('formats CNPJ correctly', function () {
            $vo = new CpfCnpj('11222333000181');
            expect($vo->formatted())->toBe('11.222.333/0001-81');
        });

        it('rejects CNPJ with all identical digits', function () {
            expect(fn () => new CpfCnpj('00000000000000'))
                ->toThrow(DomainException::class, 'todos os dígitos são iguais');
        });

        it('rejects CNPJ with wrong check digits', function () {
            expect(fn () => new CpfCnpj('11222333000100'))
                ->toThrow(DomainException::class, 'dígitos verificadores incorretos');
        });
    });

    it('two VOs with the same value are equal', function () {
        $a = new CpfCnpj('52998224725');
        $b = new CpfCnpj('529.982.247-25');
        expect($a->equals($b))->toBeTrue();
    });

    it('__toString returns the raw digits', function () {
        $vo = new CpfCnpj('52998224725');
        expect((string) $vo)->toBe('52998224725');
    });
});
