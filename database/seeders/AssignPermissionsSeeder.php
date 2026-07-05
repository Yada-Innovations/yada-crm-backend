<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AssignPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Get all permissions
        $permissions = Permission::all();
        
        // Get admin user
        $admin = User::where('email', 'admin@yadacrm.com')->first();
        
        if ($admin) {
            // Assign all permissions to admin
            $admin->syncPermissions($permissions);
            echo "Admin assigned " . $admin->permissions()->count() . " permissions\n";
        }
        
        // Get admin role
        $adminRole = Role::where('name', 'admin')->first();
        
        if ($adminRole) {
            // Assign all permissions to admin role
            $adminRole->syncPermissions($permissions);
            echo "Admin role assigned " . $adminRole->permissions()->count() . " permissions\n";
        }
        
        echo "Permissions assigned successfully!\n";
    }
}