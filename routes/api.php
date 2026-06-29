<?php

use Illuminate\Http\Request;
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
use App\Http\Controllers\Api\CommunicationController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\EmailTemplateController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\LeaveRequestController;
use App\Http\Controllers\Api\PayrollController;

// ── Public Routes ──
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// ── Authenticated Routes ──
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // ── Leads ──
    Route::get('/leads', [LeadController::class, 'index'])->middleware('permission:leads.view');
    Route::post('/leads', [LeadController::class, 'store'])->middleware('permission:leads.create');
    Route::get('/leads/{lead}', [LeadController::class, 'show'])->middleware('permission:leads.view');
    Route::patch('/leads/{lead}', [LeadController::class, 'update'])->middleware('permission:leads.edit');
    Route::delete('/leads/{lead}', [LeadController::class, 'destroy'])->middleware('permission:leads.delete');
    Route::post('/leads/{lead}/signature', [LeadController::class, 'saveSignature'])->middleware('permission:leads.edit');
    Route::post('/leads/{lead}/disqualify', [LeadController::class, 'disqualify'])->middleware('permission:leads.edit');

    // ── Quotes ──
    Route::get('/quotes', [QuoteController::class, 'index'])->middleware('permission:quotes.view');
    Route::post('/quotes', [QuoteController::class, 'store'])->middleware('permission:quotes.create');
    Route::get('/quotes/{quote}', [QuoteController::class, 'show'])->middleware('permission:quotes.view');
    Route::patch('/quotes/{quote}', [QuoteController::class, 'update'])->middleware('permission:quotes.edit');
    Route::delete('/quotes/{quote}', [QuoteController::class, 'destroy'])->middleware('permission:quotes.delete');

    // ── Clients ──
    Route::get('/clients', [ClientController::class, 'index'])->middleware('permission:clients.view');
    Route::post('/clients', [ClientController::class, 'store'])->middleware('permission:clients.create');
    Route::get('/clients/{client}', [ClientController::class, 'show'])->middleware('permission:clients.view');
    Route::patch('/clients/{client}', [ClientController::class, 'update'])->middleware('permission:clients.edit');
    Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->middleware('permission:clients.delete');

    // ── Subscriptions ──
    Route::get('/subscription-plans', [SubscriptionController::class, 'plans'])->middleware('permission:subscriptions.view');
    Route::get('/subscriptions', [SubscriptionController::class, 'index'])->middleware('permission:subscriptions.view');
    Route::post('/subscriptions', [SubscriptionController::class, 'store'])->middleware('permission:subscriptions.create');
    Route::get('/subscriptions/{subscription}', [SubscriptionController::class, 'show'])->middleware('permission:subscriptions.view');
    Route::patch('/subscriptions/{subscription}', [SubscriptionController::class, 'update'])->middleware('permission:subscriptions.edit');
    Route::delete('/subscriptions/{subscription}', [SubscriptionController::class, 'destroy'])->middleware('permission:subscriptions.delete');

    // ── Tickets ──
    Route::get('/tickets', [TicketController::class, 'index'])->middleware('permission:tickets.view');
    Route::post('/tickets', [TicketController::class, 'store'])->middleware('permission:tickets.create');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->middleware('permission:tickets.view');
    Route::patch('/tickets/{ticket}', [TicketController::class, 'update'])->middleware('permission:tickets.edit');
    Route::delete('/tickets/{ticket}', [TicketController::class, 'destroy'])->middleware('permission:tickets.delete');

    // ── Feature Requests ──
    Route::get('/feature-requests', [FeatureRequestController::class, 'index'])->middleware('permission:feature_requests.view');
    Route::post('/feature-requests', [FeatureRequestController::class, 'store'])->middleware('permission:feature_requests.create');
    Route::get('/feature-requests/{featureRequest}', [FeatureRequestController::class, 'show'])->middleware('permission:feature_requests.view');
    Route::patch('/feature-requests/{featureRequest}', [FeatureRequestController::class, 'update'])->middleware('permission:feature_requests.edit');
    Route::delete('/feature-requests/{featureRequest}', [FeatureRequestController::class, 'destroy'])->middleware('permission:feature_requests.delete');
    Route::post('/feature-requests/{featureRequest}/vote', [FeatureRequestController::class, 'vote'])->middleware('permission:feature_requests.create');

    // ── Invoices ──
    Route::get('/invoices', [InvoiceController::class, 'index'])->middleware('permission:invoices.view');
    Route::post('/invoices', [InvoiceController::class, 'store'])->middleware('permission:invoices.create');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->middleware('permission:invoices.view');
    Route::patch('/invoices/{invoice}', [InvoiceController::class, 'update'])->middleware('permission:invoices.edit');
    Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy'])->middleware('permission:invoices.delete');
    Route::post('/invoices/{invoice}/send-reminder', [InvoiceController::class, 'sendReminder'])->middleware('permission:invoices.edit');

    // ── Communications ──
    Route::get('/communications', [CommunicationController::class, 'index'])->middleware('permission:clients.view');
    Route::post('/communications', [CommunicationController::class, 'store'])->middleware('permission:clients.edit');
    Route::get('/communications/{communication}', [CommunicationController::class, 'show'])->middleware('permission:clients.view');
    Route::patch('/communications/{communication}', [CommunicationController::class, 'update'])->middleware('permission:clients.edit');
    Route::delete('/communications/{communication}', [CommunicationController::class, 'destroy'])->middleware('permission:clients.delete');
    Route::get('/clients/{client}/timeline', [CommunicationController::class, 'timeline'])->middleware('permission:clients.view');
    Route::get('/communication/templates', [CommunicationController::class, 'templates'])->middleware('permission:clients.view');

    // ── Notifications ──
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);

    // ── Email Templates ──
    Route::get('/email-templates', [EmailTemplateController::class, 'index'])->middleware('permission:clients.view');
    Route::post('/email-templates', [EmailTemplateController::class, 'store'])->middleware('permission:clients.edit');
    Route::get('/email-templates/{emailTemplate}', [EmailTemplateController::class, 'show'])->middleware('permission:clients.view');
    Route::put('/email-templates/{emailTemplate}', [EmailTemplateController::class, 'update'])->middleware('permission:clients.edit');
    Route::delete('/email-templates/{emailTemplate}', [EmailTemplateController::class, 'destroy'])->middleware('permission:clients.delete');

    // ── Employees ──
    Route::middleware(['auth:sanctum', 'permission:users.view'])->group(function () {
        Route::get('/employees', [EmployeeController::class, 'index']);
        Route::post('/employees', [EmployeeController::class, 'store'])->middleware('permission:users.create');
        Route::get('/employees/{employee}', [EmployeeController::class, 'show']);
        Route::patch('/employees/{employee}', [EmployeeController::class, 'update'])->middleware('permission:users.edit');
        Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->middleware('permission:users.delete');
        Route::get('/employees/stats', [EmployeeController::class, 'stats']);
    });

    // ── Attendance ──
    Route::middleware(['auth:sanctum', 'permission:users.view'])->group(function () {
        Route::get('/attendance', [AttendanceController::class, 'index']);
        Route::post('/attendance', [AttendanceController::class, 'store'])->middleware('permission:users.edit');
        Route::get('/attendance/{attendance}', [AttendanceController::class, 'show']);
        Route::patch('/attendance/{attendance}', [AttendanceController::class, 'update'])->middleware('permission:users.edit');
        Route::delete('/attendance/{attendance}', [AttendanceController::class, 'destroy'])->middleware('permission:users.delete');
        Route::post('/attendance/bulk-checkin', [AttendanceController::class, 'bulkCheckIn'])->middleware('permission:users.edit');
        Route::get('/attendance/summary/{employee}', [AttendanceController::class, 'summary']);
    });

    // ── Leave Requests ──
    Route::middleware(['auth:sanctum', 'permission:users.view'])->group(function () {
        Route::get('/leave-requests', [LeaveRequestController::class, 'index']);
        Route::post('/leave-requests', [LeaveRequestController::class, 'store'])->middleware('permission:users.edit');
        Route::get('/leave-requests/{leaveRequest}', [LeaveRequestController::class, 'show']);
        Route::patch('/leave-requests/{leaveRequest}', [LeaveRequestController::class, 'update'])->middleware('permission:users.edit');
        Route::delete('/leave-requests/{leaveRequest}', [LeaveRequestController::class, 'destroy'])->middleware('permission:users.delete');
        Route::post('/leave-requests/{leaveRequest}/approve', [LeaveRequestController::class, 'approve'])->middleware('permission:users.edit');
        Route::post('/leave-requests/{leaveRequest}/reject', [LeaveRequestController::class, 'reject'])->middleware('permission:users.edit');
        Route::get('/leave-requests/balance/{employee}', [LeaveRequestController::class, 'balance']);
    });

    // ── Payroll ──
    Route::middleware(['auth:sanctum', 'permission:users.view'])->group(function () {
        Route::get('/payroll', [PayrollController::class, 'index']);
        Route::get('/payroll/calculate/{employee}', [PayrollController::class, 'calculate']);
        Route::post('/payroll/generate', [PayrollController::class, 'generate'])->middleware('permission:users.edit');
        Route::get('/payroll/{payroll}', [PayrollController::class, 'show']);
        Route::patch('/payroll/{payroll}/approve', [PayrollController::class, 'approve'])->middleware('permission:users.edit');
        Route::patch('/payroll/{payroll}/paid', [PayrollController::class, 'markPaid'])->middleware('permission:users.edit');
        Route::get('/payroll/summary', [PayrollController::class, 'summary']);
    });

    // ── Admin Routes ──
    Route::middleware(['auth:sanctum', 'permission:users.view'])->prefix('admin')->group(function () {
        
        // Get all users with roles and permissions
        Route::get('/users', function () {
            return response()->json(\App\Models\User::with('roles.permissions')->get());
        });
        
        // Get all roles with permissions
        Route::get('/roles', function () {
            return response()->json(\Spatie\Permission\Models\Role::with('permissions')->get());
        });
        
        // ── Create a new role ──
        Route::post('/roles', function (Request $request) {
            try {
                $data = $request->validate([
                    'name' => 'required|string|unique:roles,name',
                    'display_name' => 'nullable|string',
                    'description' => 'nullable|string',
                    'permissions' => 'nullable|array',
                ]);

                $role = \Spatie\Permission\Models\Role::create([
                    'name' => $data['name'],
                    'guard_name' => 'web',
                ]);

                if (!empty($data['permissions'])) {
                    $role->syncPermissions($data['permissions']);
                }

                app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

                return response()->json([
                    'message' => 'Role created successfully',
                    'role' => $role->load('permissions'),
                ], 201);
                
            } catch (\Exception $e) {
                return response()->json([
                    'error' => 'Failed to create role: ' . $e->getMessage(),
                ], 500);
            }
        })->middleware('permission:users.edit');
        
        // Update role permissions
        Route::patch('/roles/{role}/permissions', function (Request $request, $roleId) {
            try {
                $role = \Spatie\Permission\Models\Role::where('id', $roleId)
                    ->where('guard_name', 'web')
                    ->first();
                
                if (!$role) {
                    return response()->json(['error' => 'Role not found'], 404);
                }
                
                $permissions = $request->input('permissions', []);
                $role->syncPermissions($permissions);
                app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
                
                return response()->json([
                    'message' => 'Permissions updated successfully',
                    'role' => $role->load('permissions'),
                ]);
                
            } catch (\Exception $e) {
                return response()->json([
                    'error' => 'Failed to update permissions: ' . $e->getMessage(),
                ], 500);
            }
        })->middleware('permission:users.edit');
        
        // Delete a user (admin only)
        Route::delete('/users/{user}', function (\App\Models\User $user) {
            try {
                $user->delete();
                return response()->json(['message' => 'User deleted successfully']);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Failed to delete user: ' . $e->getMessage()], 500);
            }
        })->middleware('permission:users.delete');
    });
});