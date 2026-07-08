<?php

use App\Http\Controllers\Auth\AuthController;
use Domain\Catalog\Presentation\Controllers\PartController;
use Domain\Catalog\Presentation\Controllers\ServiceController;
use Domain\Customer\Presentation\Controllers\CustomerController;
use Domain\Customer\Presentation\Controllers\VehicleController;
use Domain\Inventory\Presentation\Controllers\PartRequestController;
use Domain\Reports\Presentation\Controllers\ReportController;
use Domain\Workshop\Presentation\Controllers\OrderServiceController;
use Domain\Workshop\Presentation\Controllers\PublicBudgetApprovalController;
use Domain\Workshop\Presentation\Controllers\PublicOsTrackingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ── Auth ─────────────────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

// ── Public routes (no authentication) ────────────────────────────────────
Route::prefix('public')->group(function () {
    Route::get('/track/{id}', [PublicOsTrackingController::class, 'show']);

    // Aprovação/rejeição de orçamento via link assinado enviado por e-mail.
    // Middleware 'signed' (ValidateSignature) valida a assinatura e retorna
    // 403 automaticamente se inválida/expirada.
    Route::middleware('signed')->group(function () {
        Route::get('order-services/{id}/approve-budget', [PublicBudgetApprovalController::class, 'approve'])
            ->name('public.os.approve-budget');
        Route::get('order-services/{id}/reject-budget',  [PublicBudgetApprovalController::class, 'reject'])
            ->name('public.os.reject-budget');
    });
});

// ── Protected routes ─────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Customers
    Route::apiResource('customers', CustomerController::class);

    // Vehicles (nested under customers, with shallow routing)
    Route::apiResource('customers.vehicles', VehicleController::class)->shallow();

    // Catalog
    Route::apiResource('parts', PartController::class);
    Route::apiResource('services', ServiceController::class);

    // Inventory — Part Requests lifecycle
    Route::apiResource('part-requests', PartRequestController::class)->only(['index', 'show', 'store']);
    Route::post('part-requests/{id}/request-purchase',      [PartRequestController::class, 'requestPurchase']);
    Route::post('part-requests/{id}/receive-from-supplier', [PartRequestController::class, 'receiveFromSupplier']);
    Route::post('part-requests/{id}/pick-up',               [PartRequestController::class, 'pickUp']);
    Route::post('part-requests/{id}/finish',                [PartRequestController::class, 'finish']);

    // Workshop — Order Services lifecycle
    Route::apiResource('order-services', OrderServiceController::class)->only(['index', 'show', 'store']);
    Route::post('order-services/{id}/send-to-analysis',     [OrderServiceController::class, 'sendToAnalysis']);
    Route::post('order-services/{id}/generate-budget',      [OrderServiceController::class, 'generateBudget']);
    Route::post('order-services/{id}/approve-budget',       [OrderServiceController::class, 'approveBudget']);
    Route::post('order-services/{id}/reject-budget',        [OrderServiceController::class, 'rejectBudget']);
    Route::post('order-services/{id}/approve-renegotiation',[OrderServiceController::class, 'approveRenegotiation']);
    Route::post('order-services/{id}/reject-renegotiation', [OrderServiceController::class, 'rejectRenegotiation']);
    Route::post('order-services/{id}/start-execution',      [OrderServiceController::class, 'startExecution']);
    Route::post('order-services/{id}/finish-execution',     [OrderServiceController::class, 'finishExecution']);
    Route::post('order-services/{id}/deliver',              [OrderServiceController::class, 'deliver']);

    // Reports
    Route::prefix('reports')->group(function () {
        Route::get('/os-summary',          [ReportController::class, 'osSummary']);
        Route::get('/revenue',             [ReportController::class, 'revenue']);
        Route::get('/low-stock',           [ReportController::class, 'lowStock']);
        Route::get('/avg-execution-time',  [ReportController::class, 'avgExecutionTime']);
    });
});
