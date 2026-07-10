<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define all modules with their permissions
        $modules = [
            'leads',
            'quotes',
            'clients',
            'subscriptions',
            'tickets',
            'feature_requests',
            'invoices',
            'analytics',
            'services',
            'work_orders',
            'payments',
            'payroll',
            'employees',
            'communications',
            'email_templates',
            'attendance',
            'leave',
            'agreements',
            'users',
            'roles',
            'procurement', // Reserved for future use
        ];

        $actions = ['view', 'create', 'edit', 'delete'];

        // Create all permissions using 'web' guard (Spatie default)
        foreach ($modules as $module) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => "{$module}.{$action}", 'guard_name' => 'web']);
            }
        }

        // Admin — gets everything
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::all());

        // Sales agent
        $sales = Role::firstOrCreate(['name' => 'sales_agent', 'guard_name' => 'web']);
        $sales->syncPermissions([
            'leads.view', 'leads.create', 'leads.edit',
            'quotes.view', 'quotes.create',
            'clients.view', 'clients.create', 'clients.edit',
            'subscriptions.view',
            'analytics.view',
            'services.view', 'services.create', 'services.edit', 'services.delete',
            'work_orders.view', 'work_orders.create',
            'payments.view',
            'invoices.view',
        ]);

        // Support agent
        $support = Role::firstOrCreate(['name' => 'support_agent', 'guard_name' => 'web']);
        $support->syncPermissions([
            'tickets.view', 'tickets.create', 'tickets.edit',
            'feature_requests.view', 'feature_requests.create',
            'clients.view',
            'communications.view', 'communications.create',
        ]);

        // Optional: Create additional roles
        /*
        // Finance role
        $finance = Role::firstOrCreate(['name' => 'finance', 'guard_name' => 'web']);
        $finance->syncPermissions([
            'invoices.view', 'invoices.create', 'invoices.edit',
            'payments.view', 'payments.create', 'payments.edit',
            'clients.view',
            'analytics.view',
        ]);

        // HR role
        $hr = Role::firstOrCreate(['name' => 'hr', 'guard_name' => 'web']);
        $hr->syncPermissions([
            'employees.view', 'employees.create', 'employees.edit',
            'payroll.view', 'payroll.create', 'payroll.edit',
            'users.view',
        ]);

        // Procurement role (when ready)
        $procurement = Role::firstOrCreate(['name' => 'procurement', 'guard_name' => 'web']);
        $procurement->syncPermissions([
            'procurement.view', 'procurement.create', 'procurement.edit',
        ]);
        */
    }
}