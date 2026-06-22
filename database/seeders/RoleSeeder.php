<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name'         => 'admin',
                'display_name' => 'Administrator',
                'description'  => 'Full system access',
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'name'         => 'sales_agent',
                'display_name' => 'Sales Agent',
                'description'  => 'Manages leads, quotes and demos',
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'name'         => 'support_agent',
                'display_name' => 'Support Agent',
                'description'  => 'Manages tickets and feature requests',
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
        ];

        DB::table('roles')->insert($roles);
    }
}