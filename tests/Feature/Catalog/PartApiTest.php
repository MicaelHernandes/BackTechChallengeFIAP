<?php

use App\Enums\UserRole;
use App\Models\User;
use Domain\Catalog\Infrastructure\Models\PartModel;

describe('Part API (Catalog)', function () {

    beforeEach(function () {
        $this->user = User::factory()->create(['role' => UserRole::Storekeeper]);
    });

    it('lists parts paginated', function () {
        PartModel::create(['code' => 'P001', 'name' => 'Peça Teste', 'unit_price' => 10.00, 'stock_quantity' => 5, 'minimum_stock' => 1, 'unit' => 'un']);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/parts')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    });

    it('creates a new part', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/parts', [
                'code'           => 'FILTRO-AR-TEST',
                'name'           => 'Filtro de Ar Test',
                'unit_price'     => 45.90,
                'stock_quantity' => 10,
                'minimum_stock'  => 2,
                'unit'           => 'un',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.code', 'FILTRO-AR-TEST')
            ->assertJsonPath('data.stock_quantity', 10);

        $this->assertDatabaseHas('parts', ['code' => 'FILTRO-AR-TEST']);
    });

    it('rejects duplicate part code', function () {
        PartModel::create(['code' => 'DUP-001', 'name' => 'Duplicada', 'unit_price' => 10.00, 'stock_quantity' => 0, 'minimum_stock' => 0, 'unit' => 'un']);

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/parts', ['code' => 'DUP-001', 'name' => 'Outra', 'unit_price' => 5.00])
            ->assertStatus(422);
    });

    it('returns 404 for non-existent part', function () {
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/parts/99999')
            ->assertNotFound();
    });

    it('updates a part (not code)', function () {
        $part = PartModel::create(['code' => 'UPD-001', 'name' => 'Original', 'unit_price' => 20.00, 'stock_quantity' => 0, 'minimum_stock' => 0, 'unit' => 'un']);

        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/parts/{$part->id}", [
                'name'       => 'Atualizada',
                'unit_price' => 25.00,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Atualizada');
    });

    it('deletes a part', function () {
        $part = PartModel::create(['code' => 'DEL-001', 'name' => 'Para Deletar', 'unit_price' => 5.00, 'stock_quantity' => 0, 'minimum_stock' => 0, 'unit' => 'un']);

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/parts/{$part->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('parts', ['id' => $part->id]);
    });

    it('flags low_stock correctly', function () {
        $part = PartModel::create(['code' => 'LOW-001', 'name' => 'Baixo Estoque', 'unit_price' => 10.00, 'stock_quantity' => 1, 'minimum_stock' => 5, 'unit' => 'un']);

        $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/parts/{$part->id}")
            ->assertOk()
            ->assertJsonPath('data.low_stock', true);
    });
});
