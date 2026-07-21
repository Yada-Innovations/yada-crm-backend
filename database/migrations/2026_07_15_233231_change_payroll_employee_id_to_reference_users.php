<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            // Drop the old FK pointing at employees, if it exists
            try {
                $table->dropForeign(['employee_id']);
            } catch (\Exception $e) {
                // No matching FK existed — safe to continue
            }
        });

        Schema::table('payrolls', function (Blueprint $table) {
            // Old column was a UUID/string referencing employees.id — drop and recreate
            $table->dropColumn('employee_id');
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->unsignedBigInteger('employee_id')->after('id');
            $table->foreign('employee_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropColumn('employee_id');
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->uuid('employee_id')->nullable()->after('id');
        });
    }
};