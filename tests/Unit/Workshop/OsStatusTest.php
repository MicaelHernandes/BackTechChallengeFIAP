<?php

use Domain\Core\Domain\Exceptions\DomainException;
use Domain\Workshop\Domain\Enums\OsStatus;

describe('OsStatus state machine', function () {

    // ── Allowed transitions ───────────────────────────────────────────────

    it('allows CREATED → IN_ANALYSIS', function () {
        expect(OsStatus::Created->canTransitionTo(OsStatus::InAnalysis))->toBeTrue();
    });

    it('allows IN_ANALYSIS → PENDING_APPROVAL', function () {
        expect(OsStatus::InAnalysis->canTransitionTo(OsStatus::PendingApproval))->toBeTrue();
    });

    it('allows PENDING_APPROVAL → APPROVED', function () {
        expect(OsStatus::PendingApproval->canTransitionTo(OsStatus::Approved))->toBeTrue();
    });

    it('allows PENDING_APPROVAL → IN_RENEGOTIATION', function () {
        expect(OsStatus::PendingApproval->canTransitionTo(OsStatus::InRenegotiation))->toBeTrue();
    });

    it('allows IN_RENEGOTIATION → APPROVED', function () {
        expect(OsStatus::InRenegotiation->canTransitionTo(OsStatus::Approved))->toBeTrue();
    });

    it('allows IN_RENEGOTIATION → REJECTED', function () {
        expect(OsStatus::InRenegotiation->canTransitionTo(OsStatus::Rejected))->toBeTrue();
    });

    it('allows APPROVED → IN_EXECUTION', function () {
        expect(OsStatus::Approved->canTransitionTo(OsStatus::InExecution))->toBeTrue();
    });

    it('allows IN_EXECUTION → EXECUTION_FINISHED', function () {
        expect(OsStatus::InExecution->canTransitionTo(OsStatus::ExecutionFinished))->toBeTrue();
    });

    it('allows EXECUTION_FINISHED → DELIVERED_AND_FINALIZED', function () {
        expect(OsStatus::ExecutionFinished->canTransitionTo(OsStatus::DeliveredAndFinalized))->toBeTrue();
    });

    // ── Rejected transitions ──────────────────────────────────────────────

    it('rejects CREATED → APPROVED (skip steps)', function () {
        expect(OsStatus::Created->canTransitionTo(OsStatus::Approved))->toBeFalse();
    });

    it('rejects APPROVED → PENDING_APPROVAL (backwards)', function () {
        expect(OsStatus::Approved->canTransitionTo(OsStatus::PendingApproval))->toBeFalse();
    });

    it('rejects DELIVERED_AND_FINALIZED → any status (terminal)', function () {
        foreach (OsStatus::cases() as $target) {
            expect(OsStatus::DeliveredAndFinalized->canTransitionTo($target))->toBeFalse();
        }
    });

    it('rejects REJECTED → any status (terminal)', function () {
        foreach (OsStatus::cases() as $target) {
            expect(OsStatus::Rejected->canTransitionTo($target))->toBeFalse();
        }
    });

    it('throws DomainException on invalid transition via guardTransitionTo', function () {
        expect(fn () => OsStatus::Created->guardTransitionTo(OsStatus::InExecution))
            ->toThrow(DomainException::class, 'Transição inválida');
    });

    // ── Terminal states ───────────────────────────────────────────────────

    it('identifies terminal states correctly', function () {
        expect(OsStatus::Rejected->isTerminal())->toBeTrue();
        expect(OsStatus::DeliveredAndFinalized->isTerminal())->toBeTrue();
        expect(OsStatus::Created->isTerminal())->toBeFalse();
        expect(OsStatus::InExecution->isTerminal())->toBeFalse();
    });

    // ── Labels ────────────────────────────────────────────────────────────

    it('has correct labels', function () {
        expect(OsStatus::Created->label())->toBe('Criada');
        expect(OsStatus::InAnalysis->label())->toBe('Em Análise');
        expect(OsStatus::PendingApproval->label())->toBe('Pendente de Aprovação');
        expect(OsStatus::InRenegotiation->label())->toBe('Em Renegociação');
        expect(OsStatus::Approved->label())->toBe('Aprovada');
        expect(OsStatus::Rejected->label())->toBe('Rejeitada/Cancelada');
        expect(OsStatus::DeliveredAndFinalized->label())->toBe('Finalizada/Entregue');
    });

    // ── Customer notification messages ────────────────────────────────────

    it('has customer notification messages for all statuses', function () {
        foreach (OsStatus::cases() as $status) {
            expect($status->customerNotificationMessage())->toBeString()->not->toBeEmpty();
        }
    });
});
