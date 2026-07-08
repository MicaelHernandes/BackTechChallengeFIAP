<?php

use Domain\Workshop\Domain\Enums\PublicOsStatus;

describe('PublicOsStatus', function () {

    it('orders priorities with Execucao first and Entregue last', function () {
        expect(PublicOsStatus::Execucao->priority())->toBeLessThan(PublicOsStatus::AguardandoAprovacao->priority());
        expect(PublicOsStatus::AguardandoAprovacao->priority())->toBeLessThan(PublicOsStatus::Diagnostico->priority());
        expect(PublicOsStatus::Diagnostico->priority())->toBeLessThan(PublicOsStatus::Recebida->priority());
        expect(PublicOsStatus::Recebida->priority())->toBeLessThan(PublicOsStatus::Finalizada->priority());
        expect(PublicOsStatus::Finalizada->priority())->toBeLessThan(PublicOsStatus::Entregue->priority());
    });

    it('has a non-empty portuguese label for every case', function () {
        foreach (PublicOsStatus::cases() as $status) {
            expect($status->label())->toBeString()->not->toBeEmpty();
        }
    });

    it('has the exact 6 statuses required by the challenge', function () {
        $values = array_map(fn (PublicOsStatus $s) => $s->value, PublicOsStatus::cases());

        expect($values)->toEqualCanonicalizing([
            'recebida', 'diagnostico', 'aguardando_aprovacao', 'execucao', 'finalizada', 'entregue',
        ]);
    });
});
