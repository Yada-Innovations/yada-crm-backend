<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Employee;
use Illuminate\Console\Command;

class MigrateEmployeesToUsers extends Command
{
    protected $signature = 'migrate:employees-to-users';
    protected $description = 'Migrate employee data to users table';

    public function handle()
    {
        $this->info('Starting migration of employees to users...');

        $employees = Employee::all();
        $count = 0;

        foreach ($employees as $employee) {
            // Check if user already exists with this email
            $user = User::where('email', $employee->email)->first();

            if ($user) {
                // Update existing user with employee data
                $this->updateUserFromEmployee($user, $employee);
                $this->info("Updated user: {$user->email}");
            } else {
                // Create new user from employee
                $user = $this->createUserFromEmployee($employee);
                $this->info("Created user: {$user->email}");
            }
            
            $count++;
        }

        $this->info("Migration completed! Processed {$count} employees.");
        return Command::SUCCESS;
    }

    private function createUserFromEmployee($employee)
    {
        $user = User::create([
            'name' => $employee->first_name . ' ' . $employee->last_name,
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
            'email' => $employee->email,
            'password' => bcrypt('password'), // Default password
            'phone' => $employee->phone,
            'position' => $employee->position,
            'department' => $employee->department,
            'employee_id' => $employee->employee_number,
            'employment_type' => $employee->employment_type ?? 'full_time',
            'hire_date' => $employee->hire_date,
            'termination_date' => $employee->termination_date,
            'address' => $employee->address,
            'emergency_contact_name' => $employee->emergency_contact_name,
            'emergency_contact_phone' => $employee->emergency_contact_phone,
            'status' => $employee->status ?? 'active',
        ]);

        // Link employee to user
        $employee->update(['user_id' => $user->id]);

        return $user;
    }

    private function updateUserFromEmployee($user, $employee)
    {
        $user->update([
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
            'phone' => $employee->phone ?? $user->phone,
            'position' => $employee->position ?? $user->position,
            'department' => $employee->department ?? $user->department,
            'employee_id' => $employee->employee_number ?? $user->employee_id,
            'employment_type' => $employee->employment_type ?? $user->employment_type ?? 'full_time',
            'hire_date' => $employee->hire_date ?? $user->hire_date,
            'termination_date' => $employee->termination_date ?? $user->termination_date,
            'address' => $employee->address ?? $user->address,
            'emergency_contact_name' => $employee->emergency_contact_name ?? $user->emergency_contact_name,
            'emergency_contact_phone' => $employee->emergency_contact_phone ?? $user->emergency_contact_phone,
            'status' => $employee->status ?? $user->status ?? 'active',
        ]);

        // Link employee to user
        $employee->update(['user_id' => $user->id]);
    }
}