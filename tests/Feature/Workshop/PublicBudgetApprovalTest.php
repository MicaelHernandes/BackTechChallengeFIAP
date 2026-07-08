<?php

use App\Enums\UserRole;
use App\Models\User;
use Domain\Catalog\Infrastructure\Models\PartModel;
use Domain\Catalog\Infrastructure\Models\ServiceModel;
use Domain\Customer\Infrastructure\Models\CustomerModel;
use Domain\Customer\Infrastructure\Models\VehicleModel;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

describe('Public Budget Approval via signed link', function () {

    beforeEach(function () {
        Mail::fake();

        $this->attendant = User::factory()->create(['role' => UserRole::Attendant]);
        $this->mechanic = User::factory()->create(['role' => UserRole::Mechanic]);

        $this->customer = CustomerModel::create([
            'name' => 'Cliente Aprovacao',
            'document' => '52998224725',
            'email' => 'aprovacao@test.com',
        ]);

        $this->vehicle = VehicleModel::create([
            'customer_id' => $this->customer->id,
            'plate' => 'APR-0001',
            'brand' => 'Honda',
            'model' => 'Civic',
            'year' => 2021,
            'color' => 'Preto',
        ]);

        $this->service = ServiceModel::create([
            'name' => 'Alinhamento',
            'base_price' => 60.00,
            'estimated_minutes' => 20,
            'description' => 'Alinhamento e balanceamento.',
            'is_active' => true,
        ]);

        $this->part = PartModel::create([
            'code' => 'APR-PART-001',
            'name' => 'Pastilha de Freio',
            'unit_price' => 40.00,
            'stock_quantity' => 10,
            'minimum_stock' => 2,
            'unit' => 'un',
        ]);
    });

    function createOsAtPendingApproval(object $test): array
    {
        $os = $test->actingAs($test->attendant, 'sanctum')
            ->postJson('/api/order-services', [
                'customer_id' => $test->customer->id,
                'vehicle_id' => $test->vehicle->id,
                'complaint' => 'Barulho na suspensão',
            ])
            ->assertCreated()
            ->json('data');

        $test->actingAs($test->mechanic, 'sanctum')
            ->postJson("/api/order-services/{$os['id']}/send-to-analysis");

        return $test->actingAs($test->mechanic, 'sanctum')
            ->postJson("/api/order-services/{$os['id']}/generate-budget", [
                'services' => [['service_id' => $test->service->id, 'quantity' => 1]],
                'parts' => [['part_id' => $test->part->id, 'quantity' => 1]],
            ])
            ->assertOk()
            ->json('data');
    }

    it('approves budget through a valid signed link without authentication', function () {
        $os = createOsAtPendingApproval($this);

        $url = URL::temporarySignedRoute('public.os.approve-budget', now()->addDays(7), ['id' => $os['id']]);

        $response = $this->getJson($url)->assertOk();

        expect($response->json('data.status'))->toBe('approved');
    });

    it('rejects budget through a valid signed link, moving OS to in_renegotiation', function () {
        $os = createOsAtPendingApproval($this);

        $url = URL::temporarySignedRoute('public.os.reject-budget', now()->addDays(7), ['id' => $os['id']]);

        $response = $this->getJson($url)->assertOk();

        expect($response->json('data.status'))->toBe('in_renegotiation');
    });

    it('returns 403 for a tampered signature', function () {
        $os = createOsAtPendingApproval($this);

        $url = URL::temporarySignedRoute('public.os.approve-budget', now()->addDays(7), ['id' => $os['id']]);

        $this->getJson($url.'&tampered=1')->assertStatus(403);
    });

    it('returns 403 for an expired signature', function () {
        $os = createOsAtPendingApproval($this);

        $url = URL::temporarySignedRoute('public.os.approve-budget', now()->subDay(), ['id' => $os['id']]);

        $this->getJson($url)->assertStatus(403);
    });

    it('returns 422 when the link is reused after the budget was already approved', function () {
        $os = createOsAtPendingApproval($this);

        $url = URL::temporarySignedRoute('public.os.approve-budget', now()->addDays(7), ['id' => $os['id']]);

        $this->getJson($url)->assertOk();
        $this->getJson($url)->assertStatus(422);
    });

    it('returns 404 for a validly-signed link pointing to a non-existent OS id', function () {
        $url = URL::temporarySignedRoute('public.os.approve-budget', now()->addDays(7), ['id' => 999999]);

        $this->getJson($url)->assertStatus(404);
    });

    it('does not require authentication headers to approve via signed link', function () {
        $os = createOsAtPendingApproval($this);

        $url = URL::temporarySignedRoute('public.os.approve-budget', now()->addDays(7), ['id' => $os['id']]);

        // Sem Sanctum::actingAs — a rota é pública, protegida só pela assinatura.
        $this->getJson($url)->assertOk();
    });
});
