<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\QuoteController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\FeatureRequestController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\DashboardController;

// ── Public ──
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);
});

// ── Authenticated ──
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me',      [AuthController::class, 'me']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // All roles: clients, leads
    Route::middleware('role:admin,sales_agent,support_agent')->group(function () {
        Route::apiResource('clients', ClientController::class);
    });

    // Admin + Sales
    Route::middleware('role:admin,sales_agent')->group(function () {
        Route::apiResource('leads',   LeadController::class);
        Route::apiResource('quotes',  QuoteController::class);
        Route::apiResource('subscriptions', SubscriptionController::class);
        Route::get('/subscription-plans', [SubscriptionController::class, 'plans']);
    });

    // Admin + Support
    Route::middleware('role:admin,support_agent')->group(function () {
        Route::apiResource('tickets', TicketController::class);
        Route::apiResource('feature-requests', FeatureRequestController::class);
        Route::post('/feature-requests/{featureRequest}/vote', [FeatureRequestController::class, 'vote']);
    });

    // Admin only
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('invoices', InvoiceController::class);
        Route::get('/admin/users', function () {
            return response()->json(\App\Models\User::with('roles')->get());
        });
    });
});