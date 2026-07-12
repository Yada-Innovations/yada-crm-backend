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
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\CommunicationController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\EmailTemplateController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\WorkDoneController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\LeaveRequestController;
use App\Http\Controllers\Api\PayrollController;
use App\Http\Controllers\Api\EmployeeAgreementController;
use App\Http\Controllers\Api\EmployeePaymentDetailController;
use App\Http\Controllers\Api\ProcurementController;
use App\Http\Controllers\Api\VaultController;

// ── Public Routes ──
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/check-email', [AuthController::class, 'checkEmail']);
});

// ── Authenticated Routes ──
Route::middleware('auth:sanctum')->group(function () {
    
    // ── Auth ──
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::patch('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
    Route::get('/auth/permissions', [AuthController::class, 'permissions']);
    Route::get('/auth/employees', [AuthController::class, 'getEmployees']);
    Route::get('/auth/stats', [AuthController::class, 'stats']);

    // ── Dashboard ──
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
    Route::prefix('invoices')->group(function () {
        Route::get('/', [InvoiceController::class, 'index'])->middleware('permission:invoices.view');
        Route::post('/', [InvoiceController::class, 'store'])->middleware('permission:invoices.create');
        Route::get('/{invoice}', [InvoiceController::class, 'show'])->middleware('permission:invoices.view');
        Route::patch('/{invoice}', [InvoiceController::class, 'update'])->middleware('permission:invoices.edit');
        Route::delete('/{invoice}', [InvoiceController::class, 'destroy'])->middleware('permission:invoices.delete');
        Route::post('/{invoice}/send-reminder', [InvoiceController::class, 'sendReminder'])->middleware('permission:invoices.edit');
        Route::get('/{invoice}/payments', [PaymentController::class, 'byInvoice'])->middleware('permission:invoices.view');
        Route::get('/stats', [InvoiceController::class, 'stats'])->middleware('permission:invoices.view');
    });

    // ── Payments ──
    Route::prefix('payments')->group(function () {
        Route::get('/', [PaymentController::class, 'index'])->middleware('permission:payments.view');
        Route::post('/', [PaymentController::class, 'store'])->middleware('permission:payments.create');
        Route::get('/invoice/{invoiceId}', [PaymentController::class, 'byInvoice'])->middleware('permission:payments.view');
        Route::get('/{payment}', [PaymentController::class, 'show'])->middleware('permission:payments.view');
        Route::patch('/{payment}', [PaymentController::class, 'update'])->middleware('permission:payments.edit');
        Route::delete('/{payment}', [PaymentController::class, 'destroy'])->middleware('permission:payments.delete');
        Route::get('/stats', [PaymentController::class, 'stats'])->middleware('permission:payments.view');
    });

    // ── Communications ──
    Route::get('/communications', [CommunicationController::class, 'index'])->middleware('permission:communications.view');
    Route::post('/communications', [CommunicationController::class, 'store'])->middleware('permission:communications.create');
    Route::get('/communications/{communication}', [CommunicationController::class, 'show'])->middleware('permission:communications.view');
    Route::patch('/communications/{communication}', [CommunicationController::class, 'update'])->middleware('permission:communications.edit');
    Route::delete('/communications/{communication}', [CommunicationController::class, 'destroy'])->middleware('permission:communications.delete');
    Route::get('/clients/{client}/timeline', [CommunicationController::class, 'timeline'])->middleware('permission:communications.view');
    Route::get('/communication/templates', [CommunicationController::class, 'templates'])->middleware('permission:communications.view');

    // ── Notifications ──
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);

    // ── Services ──
    Route::prefix('services')->group(function () {
        Route::get('/', [ServiceController::class, 'index'])->middleware('permission:services.view');
        Route::post('/', [ServiceController::class, 'store'])->middleware('permission:services.create');
        Route::get('/available', [ServiceController::class, 'available'])->middleware('permission:services.view');
        Route::get('/category/{category}', [ServiceController::class, 'byCategory'])->middleware('permission:services.view');
        Route::get('/{service}', [ServiceController::class, 'show'])->middleware('permission:services.view');
        Route::patch('/{service}', [ServiceController::class, 'update'])->middleware('permission:services.edit');
        Route::patch('/{service}/status', [ServiceController::class, 'updateStatus'])->middleware('permission:services.edit');
        Route::delete('/{service}', [ServiceController::class, 'destroy'])->middleware('permission:services.delete');
    });

    // ── Work Orders ──
    Route::prefix('work-orders')->group(function () {
        Route::get('/', [WorkDoneController::class, 'index'])->middleware('permission:work_orders.view');
        Route::post('/', [WorkDoneController::class, 'store'])->middleware('permission:work_orders.create');
        Route::get('/stats', [WorkDoneController::class, 'stats'])->middleware('permission:work_orders.view');
        Route::get('/recent', [WorkDoneController::class, 'recent'])->middleware('permission:work_orders.view');
        Route::get('/client/{clientId}', [WorkDoneController::class, 'byClient'])->middleware('permission:work_orders.view');
        Route::get('/status/{status}', [WorkDoneController::class, 'byStatus'])->middleware('permission:work_orders.view');
        Route::post('/date-range', [WorkDoneController::class, 'byDateRange'])->middleware('permission:work_orders.view');
        Route::post('/bulk-status', [WorkDoneController::class, 'bulkUpdateStatus'])->middleware('permission:work_orders.edit');
        Route::get('/{workOrder}', [WorkDoneController::class, 'show'])->middleware('permission:work_orders.view');
        Route::patch('/{workOrder}', [WorkDoneController::class, 'update'])->middleware('permission:work_orders.edit');
        Route::patch('/{workOrder}/status', [WorkDoneController::class, 'updateStatus'])->middleware('permission:work_orders.edit');
        Route::post('/{workOrder}/invoice', [WorkDoneController::class, 'createInvoice'])->middleware('permission:work_orders.edit');
        Route::delete('/{workOrder}', [WorkDoneController::class, 'destroy'])->middleware('permission:work_orders.delete');
    });

    // ── Email Templates ──
    Route::get('/email-templates', [EmailTemplateController::class, 'index'])->middleware('permission:email_templates.view');
    Route::post('/email-templates', [EmailTemplateController::class, 'store'])->middleware('permission:email_templates.create');
    Route::get('/email-templates/{emailTemplate}', [EmailTemplateController::class, 'show'])->middleware('permission:email_templates.view');
    Route::put('/email-templates/{emailTemplate}', [EmailTemplateController::class, 'update'])->middleware('permission:email_templates.edit');
    Route::delete('/email-templates/{emailTemplate}', [EmailTemplateController::class, 'destroy'])->middleware('permission:email_templates.delete');

    // ── Employees (Unified with Users) ──
    Route::prefix('employees')->group(function () {
        Route::get('/', [EmployeeController::class, 'index'])->middleware('permission:employees.view');
        Route::post('/', [EmployeeController::class, 'store'])->middleware('permission:employees.create');
        Route::get('/stats', [EmployeeController::class, 'stats'])->middleware('permission:employees.view');
        Route::get('/{employee}', [EmployeeController::class, 'show'])->middleware('permission:employees.view');
        Route::patch('/{employee}', [EmployeeController::class, 'update'])->middleware('permission:employees.edit');
        Route::delete('/{employee}', [EmployeeController::class, 'destroy'])->middleware('permission:employees.delete');
        Route::get('/{employee}/attendance', [EmployeeController::class, 'attendance'])->middleware('permission:employees.view');
        Route::get('/{employee}/leave', [EmployeeController::class, 'leave'])->middleware('permission:employees.view');
        Route::get('/{employee}/agreements', [EmployeeController::class, 'agreements'])->middleware('permission:employees.view');
        Route::get('/{employee}/payment', [EmployeeController::class, 'payment'])->middleware('permission:employees.view');
    });

    // ── Attendance ──
    Route::prefix('attendance')->group(function () {
        Route::get('/', [AttendanceController::class, 'index'])->middleware('permission:attendance.view');
        Route::post('/', [AttendanceController::class, 'store'])->middleware('permission:attendance.create');
        Route::post('/check-in', [AttendanceController::class, 'checkIn'])->middleware('permission:attendance.create');
        Route::post('/check-out', [AttendanceController::class, 'checkOut'])->middleware('permission:attendance.create');
        Route::get('/today', [AttendanceController::class, 'today'])->middleware('permission:attendance.view');
        Route::get('/summary/{user}', [AttendanceController::class, 'summary'])->middleware('permission:attendance.view');
        Route::get('/{attendance}', [AttendanceController::class, 'show'])->middleware('permission:attendance.view');
        Route::patch('/{attendance}', [AttendanceController::class, 'update'])->middleware('permission:attendance.edit');
        Route::delete('/{attendance}', [AttendanceController::class, 'destroy'])->middleware('permission:attendance.delete');
    });

    // ── Leave Requests ──
    Route::prefix('leave-requests')->group(function () {
        Route::get('/', [LeaveRequestController::class, 'index'])->middleware('permission:leave.view');
        Route::post('/', [LeaveRequestController::class, 'store'])->middleware('permission:leave.create');
        Route::get('/{leaveRequest}', [LeaveRequestController::class, 'show'])->middleware('permission:leave.view');
        Route::patch('/{leaveRequest}', [LeaveRequestController::class, 'update'])->middleware('permission:leave.edit');
        Route::delete('/{leaveRequest}', [LeaveRequestController::class, 'destroy'])->middleware('permission:leave.delete');
        Route::post('/{leaveRequest}/approve', [LeaveRequestController::class, 'approve'])->middleware('permission:leave.edit');
        Route::post('/{leaveRequest}/reject', [LeaveRequestController::class, 'reject'])->middleware('permission:leave.edit');
        Route::get('/balance/{user}', [LeaveRequestController::class, 'balance'])->middleware('permission:leave.view');
    });

    // ── Employee Agreements ──
    Route::prefix('employee-agreements')->group(function () {
        Route::get('/', [EmployeeAgreementController::class, 'index'])->middleware('permission:agreements.view');
        Route::post('/', [EmployeeAgreementController::class, 'store'])->middleware('permission:agreements.create');
        Route::get('/{agreement}', [EmployeeAgreementController::class, 'show'])->middleware('permission:agreements.view');
        Route::patch('/{agreement}', [EmployeeAgreementController::class, 'update'])->middleware('permission:agreements.edit');
        Route::delete('/{agreement}', [EmployeeAgreementController::class, 'destroy'])->middleware('permission:agreements.delete');
    });

    // ── Employee Payment Details ──
    Route::prefix('employee-payment-details')->group(function () {
        Route::post('/', [EmployeePaymentDetailController::class, 'store'])->middleware('permission:payments.edit');
        Route::patch('/{payment}', [EmployeePaymentDetailController::class, 'update'])->middleware('permission:payments.edit');
        Route::get('/{user}', [EmployeePaymentDetailController::class, 'show'])->middleware('permission:payments.view');
    });

    // ── Payroll ──
    Route::prefix('payroll')->group(function () {
        Route::get('/', [PayrollController::class, 'index'])->middleware('permission:payroll.view');
        Route::get('/summary', [PayrollController::class, 'summary'])->middleware('permission:payroll.view');
        Route::get('/stats', [PayrollController::class, 'stats'])->middleware('permission:payroll.view');
        Route::get('/calculate/{user}', [PayrollController::class, 'calculate'])->middleware('permission:payroll.view');
        Route::post('/generate', [PayrollController::class, 'generate'])->middleware('permission:payroll.create');
        Route::post('/bulk-generate', [PayrollController::class, 'bulkGenerate'])->middleware('permission:payroll.create');
        Route::get('/{payroll}', [PayrollController::class, 'show'])->middleware('permission:payroll.view');
        Route::patch('/{payroll}', [PayrollController::class, 'update'])->middleware('permission:payroll.edit');
        Route::patch('/{payroll}/approve', [PayrollController::class, 'approve'])->middleware('permission:payroll.edit');
        Route::patch('/{payroll}/paid', [PayrollController::class, 'markPaid'])->middleware('permission:payroll.edit');
        Route::delete('/{payroll}', [PayrollController::class, 'destroy'])->middleware('permission:payroll.delete');
    });

    // ── Procurement ──
    Route::prefix('procurement')->group(function () {
        // Vendors
        Route::get('/vendors', [ProcurementController::class, 'vendorsIndex'])->middleware('permission:procurement.view');
        Route::post('/vendors', [ProcurementController::class, 'vendorsStore'])->middleware('permission:procurement.create');
        Route::get('/vendors/{vendor}', [ProcurementController::class, 'vendorsShow'])->middleware('permission:procurement.view');
        Route::patch('/vendors/{vendor}', [ProcurementController::class, 'vendorsUpdate'])->middleware('permission:procurement.edit');
        Route::delete('/vendors/{vendor}', [ProcurementController::class, 'vendorsDestroy'])->middleware('permission:procurement.delete');

        // Requisitions
        Route::get('/requisitions', [ProcurementController::class, 'requisitionsIndex'])->middleware('permission:procurement.view');
        Route::post('/requisitions', [ProcurementController::class, 'requisitionsStore'])->middleware('permission:procurement.create');
        Route::get('/requisitions/{requisition}', [ProcurementController::class, 'requisitionsShow'])->middleware('permission:procurement.view');
        Route::patch('/requisitions/{requisition}', [ProcurementController::class, 'requisitionsUpdate'])->middleware('permission:procurement.edit');
        Route::delete('/requisitions/{requisition}', [ProcurementController::class, 'requisitionsDestroy'])->middleware('permission:procurement.delete');
        Route::post('/requisitions/{requisition}/approve', [ProcurementController::class, 'requisitionsApprove'])->middleware('permission:procurement.edit');
        Route::post('/requisitions/{requisition}/reject', [ProcurementController::class, 'requisitionsReject'])->middleware('permission:procurement.edit');

        // Purchases
        Route::get('/purchases', [ProcurementController::class, 'purchasesIndex'])->middleware('permission:procurement.view');
        Route::post('/purchases', [ProcurementController::class, 'purchasesStore'])->middleware('permission:procurement.create');
        Route::get('/purchases/{purchase}', [ProcurementController::class, 'purchasesShow'])->middleware('permission:procurement.view');
        Route::patch('/purchases/{purchase}', [ProcurementController::class, 'purchasesUpdate'])->middleware('permission:procurement.edit');
        Route::delete('/purchases/{purchase}', [ProcurementController::class, 'purchasesDestroy'])->middleware('permission:procurement.delete');

        // Stats
        Route::get('/stats', [ProcurementController::class, 'stats'])->middleware('permission:procurement.view');
    });

    // ── Company Vault ──
    Route::prefix('vault')->group(function () {
        Route::get('/', [VaultController::class, 'index'])->middleware('permission:vault.view');
        Route::post('/', [VaultController::class, 'store'])->middleware('permission:vault.create');
        Route::get('/{vault}', [VaultController::class, 'show'])->middleware('permission:vault.view');
        Route::patch('/{vault}', [VaultController::class, 'update'])->middleware('permission:vault.edit');
        Route::delete('/{vault}', [VaultController::class, 'destroy'])->middleware('permission:vault.delete');
    });

    // ── Admin Routes ──
    Route::middleware(['auth:sanctum', 'permission:users.view'])->prefix('admin')->group(function () {
        
        // ── Users (Admin only) ── FIXED: Removed 'permissions' from eager load
        Route::get('/users', function () {
            $users = \App\Models\User::with(['roles', 'paymentDetail'])->get();
            return response()->json($users);
        });
        
        Route::patch('/users/{user}', function (Request $request, \App\Models\User $user) {
            try {
                $data = $request->validate([
                    'name' => 'sometimes|string|max:255',
                    'email' => 'sometimes|email|unique:users,email,' . $user->id,
                    'phone' => 'nullable|string|max:20',
                    'department' => 'nullable|string|max:255',
                    'position' => 'nullable|string|max:255',
                    'status' => 'nullable|in:active,inactive,suspended,terminated',
                    'role' => 'nullable|in:admin,sales_agent,support_agent',
                ]);

                $user->update($data);

                if (isset($data['role'])) {
                    $user->syncRoles([$data['role']]);
                }

                return response()->json($user->load(['roles', 'paymentDetail'])); // FIXED: Removed 'permissions'
            } catch (\Exception $e) {
                return response()->json(['error' => 'Failed to update user: ' . $e->getMessage()], 500);
            }
        })->middleware('permission:users.edit');
        
        Route::delete('/users/{user}', function (\App\Models\User $user) {
            try {
                $user->delete();
                return response()->json(['message' => 'User deleted successfully']);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Failed to delete user: ' . $e->getMessage()], 500);
            }
        })->middleware('permission:users.delete');

        // ── Roles ── FIXED: Removed 'permissions' from eager load
        Route::get('/roles', function () {
            $roles = \Spatie\Permission\Models\Role::with('permissions')->get();
            return response()->json($roles);
        });
        
        Route::post('/roles', function (Request $request) {
            try {
                $data = $request->validate([
                    'name' => 'required|string|unique:roles,name',
                    'display_name' => 'nullable|string',
                    'description' => 'nullable|string',
                    'guard_name' => 'nullable|string|in:web,api',
                    'permissions' => 'nullable|array',
                ]);

                $role = \Spatie\Permission\Models\Role::create([
                    'name' => $data['name'],
                    'guard_name' => $data['guard_name'] ?? 'web',
                ]);

                if (!empty($data['permissions'])) {
                    $role->syncPermissions($data['permissions']);
                }

                app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

                return response()->json([
                    'message' => 'Role created successfully',
                    'role' => $role,
                ], 201);
                
            } catch (\Exception $e) {
                return response()->json([
                    'error' => 'Failed to create role: ' . $e->getMessage(),
                ], 500);
            }
        })->middleware('permission:roles.create');
        
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
                    'role' => $role,
                ]);
                
            } catch (\Exception $e) {
                return response()->json([
                    'error' => 'Failed to update permissions: ' . $e->getMessage(),
                ], 500);
            }
        })->middleware('permission:roles.edit');
        
        Route::delete('/roles/{role}', function ($roleId) {
            try {
                $role = \Spatie\Permission\Models\Role::where('id', $roleId)
                    ->where('guard_name', 'web')
                    ->first();
                
                if (!$role) {
                    return response()->json(['error' => 'Role not found'], 404);
                }
                
                $userCount = \App\Models\User::whereHas('roles', function($q) use ($roleId) {
                    $q->where('role_id', $roleId);
                })->count();
                
                if ($userCount > 0) {
                    return response()->json([
                        'error' => 'Cannot delete role. It has ' . $userCount . ' users assigned.'
                    ], 422);
                }
                
                $roleName = $role->name;
                $role->delete();
                app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
                
                return response()->json([
                    'message' => 'Role "' . $roleName . '" deleted successfully'
                ]);
                
            } catch (\Exception $e) {
                return response()->json([
                    'error' => 'Failed to delete role: ' . $e->getMessage(),
                ], 500);
            }
        })->middleware('permission:roles.delete');
    });
});