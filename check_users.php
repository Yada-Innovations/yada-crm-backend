<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

$users = User::with('roles')->get();

echo "=== USERS AND ROLES ===\n\n";
echo "Total users: " . $users->count() . "\n\n";

foreach ($users as $user) {
    echo "ID: " . $user->id . "\n";
    echo "Name: " . $user->name . "\n";
    echo "Email: " . $user->email . "\n";
    echo "Employee ID: " . ($user->employee_id ?? 'Not set') . "\n";
    $roleNames = $user->roles->pluck('name')->toArray();
    echo "Roles: " . (count($roleNames) > 0 ? implode(', ', $roleNames) : 'No roles assigned') . "\n";
    echo "---\n";
}
