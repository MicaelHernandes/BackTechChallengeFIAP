<?php

use App\Enums\UserRole;
use App\Models\User;
use Domain\Catalog\Infrastructure\Models\PartModel;
use Domain\Catalog\Infrastructure\Models\ServiceModel;
use Domain\Customer\Infrastructure\Models\CustomerModel;
use Domain\Customer\Infrastructure\Models\VehicleModel;
use Domain\Inventory\Infrastructure\Models\PartRequestModel;
use Domain\Workshop\Infrastructure\Models\OrderServiceModel;
use Illuminate\Support\Facades\Mail;

describe('OrderService API — full lifecycle', function () {

    beforeEach(function () {
        Mail::fake();

        $this->attendant  = User::factory()->create(['role' => UserRole::Attendant]);
        $this->mechanic   = User::factory()->create(['role' => UserRole::Mechanic]);
        $this->storekeeper = User::factory()->create(['role' => UserRole::Storekeeper]);

        $this->customer = CustomerModel::create([
            'name'     => 'Carlos OS Test',
            'document' => '52998224725',
            'email'    => 'carlos@test.com',
        ]);

        $this->vehicle = VehicleModel::create([
            'customer_id' => $this->customer->id,
            'plate'       => 'TST-0001',
            'brand'       => 'Toyota',
            'model'       => 'Corolla',
            'year'        => 2022,
            'color'       => 'Prata',
        ]);

        $this->service = ServiceModel::create([
            'name'               => 'Troca de Óleo',
            'base_price'         => 80.00,
            'estimated_minutes'  => 30,
            'description'        => 'Troca de óleo do motor.',
            'is_active'          => true,
        ]);

        $this->part = PartModel::create([
            'code'           => 'TEST-PART-001',
            'name'           => 'Filtro de Óleo',
            'unit_price'     => 25.00,
            'stock_quantity' => 10,
            'minimum_stock'  => 2,
            'unit'           => 'un',
        ]);
    });

    // ── Helpers ───────────────────────────────────────────────────────────

    function createOs(object $test): array
    {
        return $test->actingAs($test->attendant, 'sanctum')
            ->postJson('/api/order-services', [
                'customer_id' => $test->customer->id,
                'vehicle_id'  => $test->vehicle->id,
                'complaint'   => 'Barulho ao frear',
            ])
            ->assertCreated()
            ->json('data');
    }

    // ── Happy path: full lifecycle ────────────────────────────────────────

    it('creates OS with status CREATED and notifies customer', function () {
        $response = $this->actingAs($this->attendant, 'sanctum')
            ->postJson('/api/order-services', [
                'customer_id' => $this->customer->id,
                'vehicle_id'  => $this->vehicle->id,
                'complaint'   => 'Carro fazendo barulho ao frear',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'created')
            ->assertJsonPath('data.customer_id', $this->customer->id)
            ->assertJsonPath('data.vehicle_id', $this->vehicle->id);

        Mail::assertSentCount(1); // NotifyCustomerOfOsStatusPolicy
    });

    it('completes full happy-path lifecycle: CREATED → DELIVERED_AND_FINALIZED', function () {
        // 1. Create → CREATED
        $os = createOs($this);
        expect($os['status'])->toBe('created');

        // 2. Send to analysis → IN_ANALYSIS
        $os = $this->actingAs($this->mechanic, 'sanctum')
            ->postJson("/api/order-services/{$os['id']}/send-to-analysis")
            ->assertOk()
            ->json('data');
        expect($os['status'])->toBe('in_analysis');

        // 3. Generate budget → PENDING_APPROVAL
        $os = $this->actingAs($this->mechanic, 'sanctum')
            ->postJson("/api/order-services/{$os['id']}/generate-budget", [
                'services' => [['service_id' => $this->service->id, 'quantity' => 1]],
                'parts'    => [['part_id' => $this->part->id, 'quantity' => 2]],
                'notes'    => 'Óleo e filtro',
            ])
            ->assertOk()
            ->json('data');

        expect($os['status'])->toBe('pending_approval');
        expect($os['budget']['total_services'])->toBe(80.0);
        expect($os['budget']['total_parts'])->toBe(50.0);
        expect($os['budget']['total_amount'])->toBe(130.0);

        // 4. Approve budget → APPROVED
        $os = $this->actingAs($this->attendant, 'sanctum')
            ->postJson("/api/order-services/{$os['id']}/approve-budget")
            ->assertOk()
            ->json('data');
        expect($os['status'])->toBe('approved');

        // 5. Start execution → IN_EXECUTION (no pending part requests)
        $os = $this->actingAs($this->mechanic, 'sanctum')
            ->postJson("/api/order-services/{$os['id']}/start-execution")
            ->assertOk()
            ->json('data');
        expect($os['status'])->toBe('in_execution');

        // 6. Finish execution → EXECUTION_FINISHED
        $os = $this->actingAs($this->mechanic, 'sanctum')
            ->postJson("/api/order-services/{$os['id']}/finish-execution")
            ->assertOk()
            ->json('data');
        expect($os['status'])->toBe('execution_finished');

        // 7. Deliver → DELIVERED_AND_FINALIZED
        $os = $this->actingAs($this->attendant, 'sanctum')
            ->postJson("/api/order-services/{$os['id']}/deliver")
            ->assertOk()
            ->json('data');
        expect($os['status'])->toBe('delivered_and_finalized');

        // 7 status transitions + 1 create = 8 emails total
        Mail::assertSentCount(8);
    });

    // ── Renegotiation flow ────────────────────────────────────────────────

    it('completes renegotiation flow: PENDING_APPROVAL → IN_RENEGOTIATION → APPROVED', function () {
        $os = createOs($this);

        // CREATED → IN_ANALYSIS
        $this->actingAs($this->mechanic, 'sanctum')
            ->postJson("/api/order-services/{$os['id']}/send-to-analysis");

        // IN_ANALYSIS → PENDING_APPROVAL
        $this->actingAs($this->mechanic, 'sanctum')
            ->postJson("/api/order-services/{$os['id']}/generate-budget", [
                'services' => [['service_id' => $this->service->id, 'quantity' => 1]],
                'parts'    => [],
            ]);

        // Reject budget → IN_RENEGOTIATION
        $os = $this->actingAs($this->attendant, 'sanctum')
            ->postJson("/api/order-services/{$os['id']}/reject-budget")
            ->assertOk()
            ->json('data');
        expect($os['status'])->toBe('in_renegotiation');

        // Approve renegotiation → APPROVED
        $os = $this->actingAs($this->attendant, 'sanctum')
            ->postJson("/api/order-services/{$os['id']}/approve-renegotiation")
            ->assertOk()
            ->json('data');
        expect($os['status'])->toBe('approved');
    });

    // ── Cancellation flow ─────────────────────────────────────────────────

    it('cancels OS via renegotiation rejection: IN_RENEGOTIATION → REJECTED', function () {
        $os = createOs($this);

        $this->actingAs($this->mechanic, 'sanctum')
            ->postJson("/api/order-services/{$os['id']}/send-to-analysis");

        $this->actingAs($this->mechanic, 'sanctum')
            ->postJson("/api/order-services/{$os['id']}/generate-budget", [
                'services' => [['service_id' => $this->service->id, 'quantity' => 1]],
                'parts'    => [],
            ]);

        $this->actingAs($this->attendant, 'sanctum')
            ->postJson("/api/order-services/{$os['id']}/reject-budget");

        // Reject renegotiation → REJECTED (cancelled)
        $os = $this->actingAs($this->attendant, 'sanctum')
            ->postJson("/api/order-services/{$os['id']}/reject-renegotiation")
            ->assertOk()
            ->json('data');
        expect($os['status'])->toBe('rejected');

        // No further transitions allowed
        $this->actingAs($this->attendant, 'sanctum')
            ->postJson("/api/order-services/{$os['id']}/approve-budget")
            ->assertStatus(422);
    });

    // ── Execution guard: pending part requests ────────────────────────────

    it('blocks start-execution when part requests are pending', function () {
        $os = createOs($this);

        $this->actingAs($this->mechanic, 'sanctum')
            ->postJson("/api/order-services/{$os['id']}/send-to-analysis");

        $this->actingAs($this->mechanic, 'sanctum')
            ->postJson("/api/order-services/{$os['id']}/generate-budget", [
                'services' => [['service_id' => $this->service->id, 'quantity' => 1]],
                'parts'    => [],
            ]);

        $this->actingAs($this->attendant, 'sanctum')
            ->postJson("/api/order-services/{$os['id']}/approve-budget");

        // Inject a part request linked to this OS in a non-terminal status
        PartRequestModel::create([
            'status'               => 'reserved',
            'requested_by_user_id' => $this->mechanic->id,
            'os_id'                => $os['id'],
            'notes'                => null,
        ]);

        $this->actingAs($this->mechanic, 'sanctum')
            ->postJson("/api/order-services/{$os['id']}/start-execution")
            ->assertStatus(422);
    });

    // ── Invalid transitions ───────────────────────────────────────────────

    it('rejects invalid transition CREATED → IN_EXECUTION', function () {
        $os = createOs($this);

        $this->actingAs($this->mechanic, 'sanctum')
            ->postJson("/api/order-services/{$os['id']}/start-execution")
            ->assertStatus(422);
    });

    it('rejects generate-budget when OS has no services and no parts', function () {
        $os = createOs($this);

        $this->actingAs($this->mechanic, 'sanctum')
            ->postJson("/api/order-services/{$os['id']}/send-to-analysis");

        $this->actingAs($this->mechanic, 'sanctum')
            ->postJson("/api/order-services/{$os['id']}/generate-budget", [
                'services' => [],
                'parts'    => [],
            ])
            ->assertStatus(422);
    });

    // ── Listing and filtering ─────────────────────────────────────────────

    it('filters order services by status', function () {
        $os1 = createOs($this);
        $os2 = createOs($this);

        // Advance os2 to IN_ANALYSIS
        $this->actingAs($this->mechanic, 'sanctum')
            ->postJson("/api/order-services/{$os2['id']}/send-to-analysis");

        $response = $this->actingAs($this->attendant, 'sanctum')
            ->getJson('/api/order-services?status=created')
            ->assertOk();

        collect($response->json('data'))->each(function ($item) {
            expect($item['status'])->toBe('created');
        });
    });

    it('returns 404 for non-existent order service', function () {
        $this->actingAs($this->attendant, 'sanctum')
            ->getJson('/api/order-services/99999')
            ->assertNotFound();
    });

    it('rejects creation when vehicle does not belong to customer', function () {
        $otherCustomer = CustomerModel::create([
            'name'     => 'Outro Cliente',
            'document' => '87748024516',
            'email'    => 'outro@test.com',
        ]);

        $this->actingAs($this->attendant, 'sanctum')
            ->postJson('/api/order-services', [
                'customer_id' => $otherCustomer->id,
                'vehicle_id'  => $this->vehicle->id, // belongs to $this->customer
                'complaint'   => 'Teste veículo errado',
            ])
            ->assertNotFound();
    });
});
