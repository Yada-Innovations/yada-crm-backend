<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Attendance table
        if (Schema::hasColumn('attendances', 'employee_id') && !Schema::hasColumn('attendances', 'user_id')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->renameColumn('employee_id', 'user_id');
            });
        }

        // Leave requests table
        if (Schema::hasColumn('leave_requests', 'employee_id') && !Schema::hasColumn('leave_requests', 'user_id')) {
            Schema::table('leave_requests', function (Blueprint $table) {
                $table->renameColumn('employee_id', 'user_id');
            });
        }

        // Employee agreements table
        if (Schema::hasColumn('employee_agreements', 'employee_id') && !Schema::hasColumn('employee_agreements', 'user_id')) {
            Schema::table('employee_agreements', function (Blueprint $table) {
                $table->renameColumn('employee_id', 'user_id');
            });
        }

        // Employee payment details already has user_id
    }

    public function down(): void
    {
        if (Schema::hasColumn('attendances', 'user_id') && !Schema::hasColumn('attendances', 'employee_id')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->renameColumn('user_id', 'employee_id');
            });
        }

        if (Schema::hasColumn('leave_requests', 'user_id') && !Schema::hasColumn('leave_requests', 'employee_id')) {
            Schema::table('leave_requests', function (Blueprint $table) {
                $table->renameColumn('user_id', 'employee_id');
            });
        }

        if (Schema::hasColumn('employee_agreements', 'user_id') && !Schema::hasColumn('employee_agreements', 'employee_id')) {
            Schema::table('employee_agreements', function (Blueprint $table) {
                $table->renameColumn('user_id', 'employee_id');
            });
        }
    }
};