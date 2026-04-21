<?php

use App\Enums\UserRole;
use App\Models\User;
use Domain\Catalog\Infrastructure\Models\PartModel;
use Domain\Inventory\Infrastructure\Models\PartRequestModel;
use Illuminate\Support\Facades\Mail;

describe('PartRequest API — full lifecycle', function () {

    beforeEach(function () {
        $this->mechanic    = User::factory()->create(['role' => UserRole::Mechanic]);
        $this->purchasing  = User::factory()->create(['role' => UserRole::Purchasing, 'email' => 'compras@test.com']);
        $this->storekeeper = User::factory()->create(['role' => UserRole::Storekeeper]);

        $this->partWithStock = PartModel::create([
            'code' => 'PART-IN-STOCK', 'name' => 'Peça com Estoque',
            'unit_price' => 20.00, 'stock_quantity' => 10, 'minimum_stock' => 2, 'unit' => 'un',
        ]);

        $this->partNoStock = PartModel::create([
            'code' => 'PART-NO-STOCK', 'name' => 'Peça sem Estoque',
            'unit_price' => 50.00, 'stock_quantity' => 0, 'minimum_stock' => 2, 'unit' => 'un',
        ]);
    });

    // ── Happy path: RESERVED flow ─────────────────────────────────────

    it('creates a part request → RESERVED when stock is available', function () {
        $response = $this->actingAs($this->mechanic, 'sanctum')
            ->postJson('/api/part-requests', [
                'items' => [['part_id' => $this->partWithStock->id, 'quantity' => 3]],
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'reserved');

        $this->assertDatabaseHas('parts', [
            'id' => $this->partWithStock->id,
            'stock_quantity' => 7, // 10 - 3
        ]);
    });

    it('completes RESERVED → PICKED_UP → FINALIZED flow', function () {
        // Create and reserve
        $pr = $this->actingAs($this->mechanic, 'sanctum')
            ->postJson('/api/part-requests', [
                'items' => [['part_id' => $this->partWithStock->id, 'quantity' => 2]],
            ])->json('data');

        expect($pr['status'])->toBe('reserved');

        // Pick up
        $this->actingAs($this->mechanic, 'sanctum')
            ->postJson("/api/part-requests/{$pr['id']}/pick-up")
            ->assertOk()
            ->assertJsonPath('data.status', 'picked_up');

        // Finish
        $this->actingAs($this->storekeeper, 'sanctum')
            ->postJson("/api/part-requests/{$pr['id']}/finish")
            ->assertOk()
            ->assertJsonPath('data.status', 'finalized');
    });

    // ── Out-of-stock flow ─────────────────────────────────────────────

    it('creates a part request → OUT_OF_STOCK when no stock and notifies purchasing', function () {
        Mail::fake();

        $response = $this->actingAs($this->mechanic, 'sanctum')
            ->postJson('/api/part-requests', [
                'items' => [['part_id' => $this->partNoStock->id, 'quantity' => 2]],
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'out_of_stock');

        Mail::assertSentCount(1); // NotifyPurchasingPolicy
    });

    it('completes full OUT_OF_STOCK flow: purchase → receive → pick up → finalize', function () {
        Mail::fake();

        // 1. Create → OUT_OF_STOCK
        $pr = $this->actingAs($this->mechanic, 'sanctum')
            ->postJson('/api/part-requests', [
                'items' => [['part_id' => $this->partNoStock->id, 'quantity' => 3]],
            ])->json('data');

        expect($pr['status'])->toBe('out_of_stock');

        // 2. Request purchase → PURCHASING
        $this->actingAs($this->purchasing, 'sanctum')
            ->postJson("/api/part-requests/{$pr['id']}/request-purchase")
            ->assertOk()
            ->assertJsonPath('data.status', 'purchasing');

        // 3. Receive from supplier → AVAILABLE_FOR_PICKUP
        $itemId = $pr['items'][0]['id'];
        $receiveResponse = $this->actingAs($this->purchasing, 'sanctum')
            ->postJson("/api/part-requests/{$pr['id']}/receive-from-supplier", [
                'supplier_name' => 'Distribuidora ABC',
                'items'         => [['part_request_item_id' => $itemId, 'quantity_received' => 3]],
            ]);

        $receiveResponse->assertOk()
            ->assertJsonPath('data.status', 'available_for_pickup');

        // AddPartsToInventoryPolicy increments stock
        $this->assertDatabaseHas('parts', [
            'id'             => $this->partNoStock->id,
            'stock_quantity' => 3, // 0 + 3
        ]);

        // Mail sent to mechanic (NotifyMechanicPolicy)
        Mail::assertSentCount(2); // 1 out-of-stock + 1 ready-for-pickup

        // 4. Pick up → PICKED_UP (stock decremented again)
        $this->actingAs($this->mechanic, 'sanctum')
            ->postJson("/api/part-requests/{$pr['id']}/pick-up")
            ->assertOk()
            ->assertJsonPath('data.status', 'picked_up');

        $this->assertDatabaseHas('parts', [
            'id'             => $this->partNoStock->id,
            'stock_quantity' => 0, // 3 - 3
        ]);

        // 5. Finish → FINALIZED
        $this->actingAs($this->storekeeper, 'sanctum')
            ->postJson("/api/part-requests/{$pr['id']}/finish")
            ->assertOk()
            ->assertJsonPath('data.status', 'finalized');
    });

    // ── Invalid transitions ───────────────────────────────────────────

    it('rejects invalid transition (RESERVED → PURCHASING)', function () {
        $pr = $this->actingAs($this->mechanic, 'sanctum')
            ->postJson('/api/part-requests', [
                'items' => [['part_id' => $this->partWithStock->id, 'quantity' => 1]],
            ])->json('data');

        // Attempt to request purchase on a RESERVED request
        $this->actingAs($this->purchasing, 'sanctum')
            ->postJson("/api/part-requests/{$pr['id']}/request-purchase")
            ->assertStatus(422);
    });

    it('returns 404 for non-existent part request', function () {
        $this->actingAs($this->storekeeper, 'sanctum')
            ->getJson('/api/part-requests/99999')
            ->assertNotFound();
    });

    it('rejects request with non-existent part_id', function () {
        $this->actingAs($this->mechanic, 'sanctum')
            ->postJson('/api/part-requests', [
                'items' => [['part_id' => 99999, 'quantity' => 1]],
            ])
            ->assertNotFound();
    });

    // ── Listing with filter ───────────────────────────────────────────

    it('filters part requests by status', function () {
        // Create one reserved and one out-of-stock request
        $this->actingAs($this->mechanic, 'sanctum')
            ->postJson('/api/part-requests', [
                'items' => [['part_id' => $this->partWithStock->id, 'quantity' => 1]],
            ]);

        $this->actingAs($this->mechanic, 'sanctum')
            ->postJson('/api/part-requests', [
                'items' => [['part_id' => $this->partNoStock->id, 'quantity' => 1]],
            ]);

        $response = $this->actingAs($this->storekeeper, 'sanctum')
            ->getJson('/api/part-requests?status=reserved')
            ->assertOk();

        collect($response->json('data'))->each(function ($item) {
            expect($item['status'])->toBe('reserved');
        });
    });
});
