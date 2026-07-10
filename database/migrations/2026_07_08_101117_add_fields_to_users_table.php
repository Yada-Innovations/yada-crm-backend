<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Contact Information
            $table->string('phone')->nullable()->after('email');
            $table->string('department')->nullable()->after('phone');
            $table->string('position')->nullable()->after('department');
            $table->string('employee_id')->nullable()->unique()->after('position');
            $table->date('hire_date')->nullable()->after('employee_id');
            
            // Address
            $table->string('address')->nullable()->after('hire_date');
            $table->string('city')->nullable()->after('address');
            $table->string('state')->nullable()->after('city');
            $table->string('country')->default('Kenya')->after('state');
            
            // Emergency Contact
            $table->string('emergency_contact_name')->nullable()->after('country');
            $table->string('emergency_contact_phone')->nullable()->after('emergency_contact_name');
            $table->string('emergency_contact_relation')->nullable()->after('emergency_contact_phone');
            
            // Account Settings
            $table->string('status')->default('active')->after('emergency_contact_relation');
            $table->string('timezone')->default('Africa/Nairobi')->after('status');
            $table->string('language')->default('en')->after('timezone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'department',
                'position',
                'employee_id',
                'hire_date',
                'address',
                'city',
                'state',
                'country',
                'emergency_contact_name',
                'emergency_contact_phone',
                'emergency_contact_relation',
                'status',
                'timezone',
                'language',
            ]);
        });
    }
};