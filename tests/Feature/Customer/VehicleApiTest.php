<?php

use App\Enums\UserRole;
use App\Models\User;
use Domain\Customer\Infrastructure\Models\CustomerModel;
use Domain\Customer\Infrastructure\Models\VehicleModel;

describe('Vehicle API', function () {

    beforeEach(function () {
        $this->user     = User::factory()->create(['role' => UserRole::Attendant]);
        $this->customer = CustomerModel::create([
            'name'     => 'Cliente Teste',
            'document' => '52998224725',
            'email'    => 'cliente@test.com',
        ]);
    });

    // ── Index ────────────────────────────────────────────────────────────

    it('lists vehicles of a customer', function () {
        VehicleModel::create([
            'customer_id' => $this->customer->id,
            'plate'       => 'ABC1D23',
            'brand'       => 'Toyota',
            'model'       => 'Corolla',
            'year'        => 2020,
            'color'       => 'Prata',
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/customers/{$this->customer->id}/vehicles")
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    });

    it('returns 401 for unauthenticated vehicle listing', function () {
        $this->getJson("/api/customers/{$this->customer->id}/vehicles")
            ->assertUnauthorized();
    });

    // ── Store ────────────────────────────────────────────────────────────

    it('creates a vehicle for a customer', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/customers/{$this->customer->id}/vehicles", [
                'plate' => 'XYZ9876',
                'brand' => 'Honda',
                'model' => 'Civic',
                'year'  => 2019,
                'color' => 'Preto',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.plate', 'XYZ-9876');

        $this->assertDatabaseHas('vehicles', ['plate' => 'XYZ9876']);
    });

    it('rejects vehicle creation with invalid plate', function () {
        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/customers/{$this->customer->id}/vehicles", [
                'plate' => '12AB345',
                'brand' => 'X',
                'model' => 'X',
                'year'  => 2020,
                'color' => 'X',
            ])
            ->assertStatus(422);
    });

    it('rejects duplicate plate', function () {
        VehicleModel::create([
            'customer_id' => $this->customer->id,
            'plate'       => 'DEF2E34',
            'brand'       => 'VW',
            'model'       => 'Gol',
            'year'        => 2018,
            'color'       => 'Branco',
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/customers/{$this->customer->id}/vehicles", [
                'plate' => 'DEF2E34',
                'brand' => 'Outro',
                'model' => 'Model',
                'year'  => 2021,
                'color' => 'Azul',
            ])
            ->assertStatus(422);
    });

    it('returns 404 when creating vehicle for non-existent customer', function () {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/customers/99999/vehicles', [
                'plate' => 'GHI3F45',
                'brand' => 'X',
                'model' => 'Y',
                'year'  => 2020,
                'color' => 'Z',
            ])
            ->assertNotFound();
    });

    // ── Show ─────────────────────────────────────────────────────────────

    it('returns a single vehicle', function () {
        $vehicle = VehicleModel::create([
            'customer_id' => $this->customer->id,
            'plate'       => 'MNO5H67',
            'brand'       => 'Fiat',
            'model'       => 'Argo',
            'year'        => 2022,
            'color'       => 'Cinza',
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/vehicles/{$vehicle->id}")
            ->assertOk()
            ->assertJsonPath('data.plate', 'MNO-5H67');
    });

    // ── Update ───────────────────────────────────────────────────────────

    it('updates a vehicle', function () {
        $vehicle = VehicleModel::create([
            'customer_id' => $this->customer->id,
            'plate'       => 'PQR6I78',
            'brand'       => 'Renault',
            'model'       => 'Sandero',
            'year'        => 2018,
            'color'       => 'Laranja',
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/vehicles/{$vehicle->id}", [
                'plate' => 'PQR6I78',
                'brand' => 'Renault',
                'model' => 'Duster',
                'year'  => 2020,
                'color' => 'Preto',
            ])
            ->assertOk()
            ->assertJsonPath('data.model', 'Duster');
    });

    // ── Destroy ──────────────────────────────────────────────────────────

    it('deletes a vehicle', function () {
        $vehicle = VehicleModel::create([
            'customer_id' => $this->customer->id,
            'plate'       => 'STU7J89',
            'brand'       => 'Hyundai',
            'model'       => 'HB20',
            'year'        => 2020,
            'color'       => 'Branco',
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/vehicles/{$vehicle->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('vehicles', ['id' => $vehicle->id]);
    });

    it('returns 404 when deleting a non-existent vehicle', function () {
        $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/vehicles/99999')
            ->assertNotFound();
    });
});
