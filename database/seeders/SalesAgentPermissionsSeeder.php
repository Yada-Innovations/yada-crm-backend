<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SalesAgentPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // ── First, create all permissions ──
        $modules = [
            'dashboard' => ['view'],
            'leads' => ['view', 'create', 'edit'],
            'quotes' => ['view', 'create'],
            'services' => ['view', 'create', 'edit'],
            'clients' => ['view', 'create', 'edit'],
            'invoices' => ['view'],
            'payments' => ['view'],
            'analytics' => ['view'],
        ];

        // Create permissions
        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                $permissionName = $module . '.' . $action;
                Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
                echo "Created permission: " . $permissionName . "\n";
            }
        }

        // ── Get or create sales agent role ──
        $salesRole = Role::firstOrCreate(['name' => 'sales_agent', 'guard_name' => 'web']);

        // ── Define sales agent permissions ──
        $salesPermissions = [
            'dashboard.view',
            'leads.view',
            'leads.create',
            'leads.edit',
            'quotes.view',
            'quotes.create',
            'services.view',
            'services.create',
            'services.edit',
            'clients.view',
            'clients.create',
            'clients.edit',
            'invoices.view',
            'payments.view',
            'analytics.view',
        ];

        // Assign permissions to role
        $salesRole->syncPermissions($salesPermissions);

        echo "\nSales agent role has " . $salesRole->permissions()->count() . " permissions\n";

        // ── Assign role to user ──
        $salesUser = User::where('email', 'sales@yadacrm.com')->first();
        
        if ($salesUser) {
            $salesUser->assignRole('sales_agent');
            echo "Sales agent user assigned role\n";
        } else {
            echo "Sales agent user not found. Create one:\n";
            echo "php artisan tinker --execute=\"use App\Models\User; use Illuminate\\Support\\Facades\\Hash; User::create(['name' => 'Sales Agent', 'email' => 'sales@yadacrm.com', 'password' => Hash::make('Livymugo@20')]);\"\n";
        }

        // ── List all permissions ──
        echo "\nSales Agent Permissions:\n";
        foreach ($salesRole->permissions as $perm) {
            echo "- " . $perm->name . "\n";
        }
    }
}