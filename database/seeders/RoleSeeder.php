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

        $modules = [
            'leads', 'quotes', 'clients', 'subscriptions',
            'tickets', 'feature_requests', 'invoices', 'users', 'analytics',
        ];
        $actions = ['view', 'create', 'edit', 'delete'];

        // Create all permissions
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
        ]);

        // Support agent
        $support = Role::firstOrCreate(['name' => 'support_agent', 'guard_name' => 'web']);
        $support->syncPermissions([
            'tickets.view', 'tickets.create', 'tickets.edit',
            'feature_requests.view', 'feature_requests.create',
            'clients.view',
        ]);
    }
}