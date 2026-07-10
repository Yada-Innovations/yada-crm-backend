<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Check if columns exist before adding
            if (!Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name')->nullable()->after('name');
            }
            if (!Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name')->nullable()->after('first_name');
            }
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'position')) {
                $table->string('position')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('users', 'department')) {
                $table->string('department')->nullable()->after('position');
            }
            if (!Schema::hasColumn('users', 'employee_id')) {
                $table->string('employee_id')->unique()->nullable()->after('department');
            }
            if (!Schema::hasColumn('users', 'employment_type')) {
                $table->string('employment_type')->default('full_time')->after('employee_id');
            }
            if (!Schema::hasColumn('users', 'hire_date')) {
                $table->date('hire_date')->nullable()->after('employment_type');
            }
            if (!Schema::hasColumn('users', 'termination_date')) {
                $table->date('termination_date')->nullable()->after('hire_date');
            }
            if (!Schema::hasColumn('users', 'address')) {
                $table->text('address')->nullable()->after('status');
            }
            if (!Schema::hasColumn('users', 'city')) {
                $table->string('city')->nullable()->after('address');
            }
            if (!Schema::hasColumn('users', 'state')) {
                $table->string('state')->nullable()->after('city');
            }
            if (!Schema::hasColumn('users', 'country')) {
                $table->string('country')->default('Kenya')->after('state');
            }
            if (!Schema::hasColumn('users', 'emergency_contact_name')) {
                $table->string('emergency_contact_name')->nullable()->after('country');
            }
            if (!Schema::hasColumn('users', 'emergency_contact_phone')) {
                $table->string('emergency_contact_phone')->nullable()->after('emergency_contact_name');
            }
            if (!Schema::hasColumn('users', 'emergency_contact_relation')) {
                $table->string('emergency_contact_relation')->nullable()->after('emergency_contact_phone');
            }
            if (!Schema::hasColumn('users', 'profile_picture')) {
                $table->string('profile_picture')->nullable()->after('emergency_contact_relation');
            }
            if (!Schema::hasColumn('users', 'timezone')) {
                $table->string('timezone')->default('Africa/Nairobi')->after('profile_picture');
            }
            if (!Schema::hasColumn('users', 'language')) {
                $table->string('language')->default('en')->after('timezone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [
                'first_name', 'last_name', 'phone', 'position', 'department',
                'employee_id', 'employment_type', 'hire_date', 'termination_date',
                'address', 'city', 'state', 'country',
                'emergency_contact_name', 'emergency_contact_phone',
                'emergency_contact_relation', 'profile_picture',
                'timezone', 'language'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};