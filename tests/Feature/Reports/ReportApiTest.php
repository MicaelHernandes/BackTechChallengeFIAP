<?php

use App\Enums\UserRole;
use App\Models\User;
use Domain\Catalog\Infrastructure\Models\PartModel;
use Domain\Customer\Infrastructure\Models\CustomerModel;
use Domain\Customer\Infrastructure\Models\VehicleModel;
use Domain\Workshop\Infrastructure\Models\BudgetModel;
use Domain\Workshop\Infrastructure\Models\OrderServiceModel;
use Illuminate\Support\Facades\Mail;

describe('Reports API', function () {

    beforeEach(function () {
        Mail::fake();
        $this->user = User::factory()->create(['role' => UserRole::Admin]);
    });

    // ── OS Summary ────────────────────────────────────────────────────────

    it('returns os-summary with counts and revenue', function () {
        $customer = CustomerModel::create(['name' => 'C1', 'document' => '52998224725', 'email' => 'c1@t.com']);
        $vehicle = VehicleModel::create(['customer_id' => $customer->id, 'plate' => 'RPT-0001', 'brand' => 'Ford', 'model' => 'Ka', 'year' => 2020, 'color' => 'Azul']);

        // One finalized OS with budget
        $os = OrderServiceModel::create([
            'status' => 'delivered_and_finalized',
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'complaint' => 'Test',
        ]);
        BudgetModel::create([
            'os_id' => $os->id,
            'total_services' => 100.00,
            'total_parts' => 50.00,
            'total_amount' => 150.00,
        ]);

        // One open OS
        OrderServiceModel::create([
            'status' => 'in_analysis',
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'complaint' => 'Test 2',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reports/os-summary')
            ->assertOk();

        expect($response->json('data.total_os'))->toBe(2);
        expect($response->json('data.revenue.finalized'))->toBe(150.0);
        expect($response->json('data.counts_by_status.delivered_and_finalized'))->toBe(1);
        expect($response->json('data.counts_by_status.in_analysis'))->toBe(1);
    });

    it('returns 0 revenue when no OS is finalized', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reports/os-summary')
            ->assertOk();

        expect($response->json('data.revenue.finalized'))->toBe(0.0);
        expect($response->json('data.total_os'))->toBe(0);
    });

    it('requires authentication for os-summary', function () {
        $this->getJson('/api/reports/os-summary')->assertUnauthorized();
    });

    // ── Revenue by period ─────────────────────────────────────────────────

    it('returns revenue filtered by period', function () {
        $customer = CustomerModel::create(['name' => 'C2', 'document' => '87748024537', 'email' => 'c2@t.com']);
        $vehicle = VehicleModel::create(['customer_id' => $customer->id, 'plate' => 'RPT-0002', 'brand' => 'GM', 'model' => 'Onix', 'year' => 2021, 'color' => 'Branco']);

        $os = OrderServiceModel::create([
            'status' => 'delivered_and_finalized',
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'complaint' => 'Revisão',
        ]);
        BudgetModel::create([
            'os_id' => $os->id,
            'total_services' => 200.00,
            'total_parts' => 80.00,
            'total_amount' => 280.00,
        ]);

        $today = now()->toDateString();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/reports/revenue?from={$today}&to={$today}")
            ->assertOk();

        expect($response->json('data.totals.total_amount'))->toBe(280.0);
        expect($response->json('data.totals.os_count'))->toBe(1);
    });

    it('rejects invalid date range (to before from)', function () {
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reports/revenue?from=2026-12-31&to=2026-01-01')
            ->assertStatus(422);
    });

    // ── Low stock ─────────────────────────────────────────────────────────

    it('returns parts at or below minimum stock', function () {
        PartModel::create(['code' => 'LOW-001', 'name' => 'Peça Baixa', 'unit_price' => 10, 'stock_quantity' => 1, 'minimum_stock' => 5, 'unit' => 'un']);
        PartModel::create(['code' => 'OK-001',  'name' => 'Peça OK',    'unit_price' => 10, 'stock_quantity' => 10, 'minimum_stock' => 5, 'unit' => 'un']);
        PartModel::create(['code' => 'ZERO-001', 'name' => 'Peça Zero',  'unit_price' => 10, 'stock_quantity' => 0, 'minimum_stock' => 2, 'unit' => 'un']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reports/low-stock')
            ->assertOk();

        $codes = collect($response->json('data'))->pluck('code');
        expect($codes)->toContain('LOW-001');
        expect($codes)->toContain('ZERO-001');
        expect($codes)->not->toContain('OK-001');
    });

    it('returns empty list when all parts have sufficient stock', function () {
        PartModel::create(['code' => 'OK-002', 'name' => 'OK', 'unit_price' => 10, 'stock_quantity' => 20, 'minimum_stock' => 5, 'unit' => 'un']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reports/low-stock')
            ->assertOk();

        expect($response->json('data'))->toBeEmpty();
    });

    it('includes correct deficit in low-stock response', function () {
        PartModel::create(['code' => 'DEF-001', 'name' => 'Deficit Peça', 'unit_price' => 10, 'stock_quantity' => 1, 'minimum_stock' => 5, 'unit' => 'un']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reports/low-stock')
            ->assertOk();

        $part = collect($response->json('data'))->firstWhere('code', 'DEF-001');
        expect($part['deficit'])->toBe(4); // 5 - 1
    });
});
