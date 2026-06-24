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

    // Dashboard — any authenticated user
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // ── Leads ──
    Route::get('/leads',            [LeadController::class, 'index'])  ->middleware('permission:leads.view');
    Route::post('/leads',           [LeadController::class, 'store'])  ->middleware('permission:leads.create');
    Route::get('/leads/{lead}',     [LeadController::class, 'show'])   ->middleware('permission:leads.view');
    Route::patch('/leads/{lead}',   [LeadController::class, 'update']) ->middleware('permission:leads.edit');
    Route::delete('/leads/{lead}',  [LeadController::class, 'destroy'])->middleware('permission:leads.delete');

    // ── Quotes ──
    Route::get('/quotes',           [QuoteController::class, 'index'])  ->middleware('permission:quotes.view');
    Route::post('/quotes',          [QuoteController::class, 'store'])  ->middleware('permission:quotes.create');
    Route::get('/quotes/{quote}',   [QuoteController::class, 'show'])   ->middleware('permission:quotes.view');
    Route::patch('/quotes/{quote}', [QuoteController::class, 'update']) ->middleware('permission:quotes.edit');
    Route::delete('/quotes/{quote}',[QuoteController::class, 'destroy'])->middleware('permission:quotes.delete');

    // ── Clients ──
    Route::get('/clients',              [ClientController::class, 'index'])  ->middleware('permission:clients.view');
    Route::post('/clients',             [ClientController::class, 'store'])  ->middleware('permission:clients.create');
    Route::get('/clients/{client}',     [ClientController::class, 'show'])   ->middleware('permission:clients.view');
    Route::patch('/clients/{client}',   [ClientController::class, 'update']) ->middleware('permission:clients.edit');
    Route::delete('/clients/{client}',  [ClientController::class, 'destroy'])->middleware('permission:clients.delete');

    // ── Subscriptions ──
    Route::get('/subscription-plans',                   [SubscriptionController::class, 'plans'])  ->middleware('permission:subscriptions.view');
    Route::get('/subscriptions',                        [SubscriptionController::class, 'index'])  ->middleware('permission:subscriptions.view');
    Route::post('/subscriptions',                       [SubscriptionController::class, 'store'])  ->middleware('permission:subscriptions.create');
    Route::get('/subscriptions/{subscription}',         [SubscriptionController::class, 'show'])   ->middleware('permission:subscriptions.view');
    Route::patch('/subscriptions/{subscription}',       [SubscriptionController::class, 'update']) ->middleware('permission:subscriptions.edit');
    Route::delete('/subscriptions/{subscription}',      [SubscriptionController::class, 'destroy'])->middleware('permission:subscriptions.delete');

    // ── Tickets ──
    Route::get('/tickets',              [TicketController::class, 'index'])  ->middleware('permission:tickets.view');
    Route::post('/tickets',             [TicketController::class, 'store'])  ->middleware('permission:tickets.create');
    Route::get('/tickets/{ticket}',     [TicketController::class, 'show'])   ->middleware('permission:tickets.view');
    Route::patch('/tickets/{ticket}',   [TicketController::class, 'update']) ->middleware('permission:tickets.edit');
    Route::delete('/tickets/{ticket}',  [TicketController::class, 'destroy'])->middleware('permission:tickets.delete');

    // ── Feature Requests ──
    Route::get('/feature-requests',                          [FeatureRequestController::class, 'index'])  ->middleware('permission:feature_requests.view');
    Route::post('/feature-requests',                         [FeatureRequestController::class, 'store'])  ->middleware('permission:feature_requests.create');
    Route::get('/feature-requests/{featureRequest}',         [FeatureRequestController::class, 'show'])   ->middleware('permission:feature_requests.view');
    Route::patch('/feature-requests/{featureRequest}',       [FeatureRequestController::class, 'update']) ->middleware('permission:feature_requests.edit');
    Route::delete('/feature-requests/{featureRequest}',      [FeatureRequestController::class, 'destroy'])->middleware('permission:feature_requests.delete');
    Route::post('/feature-requests/{featureRequest}/vote',   [FeatureRequestController::class, 'vote'])   ->middleware('permission:feature_requests.create');

    // ── Invoices (admin only) ──
    Route::get('/invoices',             [InvoiceController::class, 'index'])  ->middleware('permission:invoices.view');
    Route::post('/invoices',            [InvoiceController::class, 'store'])  ->middleware('permission:invoices.create');
    Route::get('/invoices/{invoice}',   [InvoiceController::class, 'show'])   ->middleware('permission:invoices.view');
    Route::patch('/invoices/{invoice}', [InvoiceController::class, 'update']) ->middleware('permission:invoices.edit');
    Route::delete('/invoices/{invoice}',[InvoiceController::class, 'destroy'])->middleware('permission:invoices.delete');

    // ── Users (admin only) ──
    Route::get('/admin/users', function () {
        return response()->json(\App\Models\User::with('roles')->get());
    })->middleware('permission:users.view');

    Route::delete('/admin/users/{user}', function (\App\Models\User $user) {
        $user->delete();
        return response()->json(['message' => 'User deleted']);
    })->middleware('permission:users.delete');
});