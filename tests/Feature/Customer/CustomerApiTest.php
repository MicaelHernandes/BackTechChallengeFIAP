<?php

use App\Enums\UserRole;
use App\Models\User;
use Domain\Customer\Infrastructure\Models\CustomerModel;

describe('Customer API', function () {

    beforeEach(function () {
        $this->user = User::factory()->create(['role' => UserRole::Attendant]);
    });

    // ── Index ────────────────────────────────────────────────────────────

    it('lists customers paginated', function () {
        CustomerModel::create([
            'name'     => 'João Teste',
            'document' => '52998224725',
            'email'    => 'joao@test.com',
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/customers')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta', 'links']);
    });

    it('returns 401 for unauthenticated requests', function () {
        $this->getJson('/api/customers')->assertUnauthorized();
    });

    // ── Show ─────────────────────────────────────────────────────────────

    it('returns a single customer', function () {
        $customer = CustomerModel::create([
            'name'     => 'Maria Teste',
            'document' => '87748024516',
            'email'    => 'maria@test.com',
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/customers/{$customer->id}")
            ->assertOk()
            ->assertJsonPath('data.document', '877.480.245-16');
    });

    it('returns 404 for a non-existent customer', function () {
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/customers/99999')
            ->assertNotFound();
    });

    // ── Store ────────────────────────────────────────────────────────────

    it('creates a new customer', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/customers', [
                'name'     => 'Novo Cliente',
                'document' => '60669377800',
                'email'    => 'novo@test.com',
                'phone'    => '(11) 91111-2222',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Novo Cliente');

        $this->assertDatabaseHas('customers', ['document' => '60669377800']);
    });

    it('rejects creation with a duplicate document', function () {
        CustomerModel::create([
            'name'     => 'Existente',
            'document' => '43936566884',
            'email'    => 'existente@test.com',
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/customers', [
                'name'     => 'Outro',
                'document' => '43936566884',
                'email'    => 'outro@test.com',
            ])
            ->assertStatus(422);
    });

    it('rejects creation with invalid CPF', function () {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/customers', [
                'name'     => 'Inválido',
                'document' => '12345678901',
                'email'    => 'invalido@test.com',
            ])
            ->assertStatus(422);
    });

    it('rejects creation with missing required fields', function () {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/customers', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'document', 'email']);
    });

    // ── Update ───────────────────────────────────────────────────────────

    it('updates an existing customer', function () {
        $customer = CustomerModel::create([
            'name'     => 'Antes',
            'document' => '09791762800',
            'email'    => 'antes@test.com',
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/customers/{$customer->id}", [
                'name'  => 'Depois',
                'email' => 'depois@test.com',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Depois');
    });

    it('returns 404 when updating a non-existent customer', function () {
        $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/customers/99999', [
                'name'  => 'X',
                'email' => 'x@test.com',
            ])
            ->assertNotFound();
    });

    // ── Destroy ──────────────────────────────────────────────────────────

    it('deletes an existing customer', function () {
        $customer = CustomerModel::create([
            'name'     => 'Para deletar',
            'document' => '18168055804',
            'email'    => 'del@test.com',
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/customers/{$customer->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
    });

    it('returns 404 when deleting a non-existent customer', function () {
        $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/customers/99999')
            ->assertNotFound();
    });
});
