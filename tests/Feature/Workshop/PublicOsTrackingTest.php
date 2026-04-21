<?php

use Domain\Customer\Infrastructure\Models\CustomerModel;
use Domain\Customer\Infrastructure\Models\VehicleModel;
use Domain\Workshop\Infrastructure\Models\OrderServiceModel;

describe('Public OS Tracking', function () {

    function makeOs(string $status = 'created'): array
    {
        $customer = CustomerModel::create([
            'name'     => 'Cliente Público',
            'document' => '52998224725',
            'email'    => 'pub@test.com',
        ]);
        $vehicle = VehicleModel::create([
            'customer_id' => $customer->id,
            'plate'       => 'PUB-0001',
            'brand'       => 'Fiat',
            'model'       => 'Strada',
            'year'        => 2023,
            'color'       => 'Vermelho',
        ]);
        $os = OrderServiceModel::create([
            'status'      => $status,
            'customer_id' => $customer->id,
            'vehicle_id'  => $vehicle->id,
            'complaint'   => 'Revisão geral',
        ]);

        return [$os, $customer, $vehicle];
    }

    it('returns OS status without authentication', function () {
        [$os, $customer, $vehicle] = makeOs('in_analysis');

        $response = $this->getJson("/api/public/track/{$os->id}")
            ->assertOk();

        expect($response->json('data.os_id'))->toBe($os->id);
        expect($response->json('data.status'))->toBe('in_analysis');
        expect($response->json('data.status_label'))->toBe('Em Análise');
        expect($response->json('data.customer_name'))->toBe('Cliente Público');
        expect($response->json('data.vehicle'))->toContain('PUB-0001');
        expect($response->json('data.is_finalized'))->toBeFalse();
    });

    it('returns correct message for each status', function () {
        [$os] = makeOs('pending_approval');

        $response = $this->getJson("/api/public/track/{$os->id}")->assertOk();

        expect($response->json('data.message'))->toContain('orçamento');
    });

    it('marks finalized OS correctly', function () {
        [$os] = makeOs('delivered_and_finalized');

        $response = $this->getJson("/api/public/track/{$os->id}")->assertOk();

        expect($response->json('data.is_finalized'))->toBeTrue();
        expect($response->json('data.status'))->toBe('delivered_and_finalized');
    });

    it('marks rejected OS as finalized', function () {
        [$os] = makeOs('rejected');

        $response = $this->getJson("/api/public/track/{$os->id}")->assertOk();

        expect($response->json('data.is_finalized'))->toBeTrue();
    });

    it('returns 404 for non-existent OS', function () {
        $this->getJson('/api/public/track/99999')->assertNotFound();
    });

    it('does not expose budget details in public response', function () {
        [$os] = makeOs('pending_approval');

        $response = $this->getJson("/api/public/track/{$os->id}")->assertOk();

        expect($response->json('data'))->not->toHaveKey('budget');
        expect($response->json('data'))->not->toHaveKey('service_items');
    });
});
