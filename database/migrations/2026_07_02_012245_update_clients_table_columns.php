<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('clients', function (Blueprint $table) {
            // Rename columns to match LeadController
            if (Schema::hasColumn('clients', 'first_name') && Schema::hasColumn('clients', 'last_name')) {
                // Drop existing columns and add new ones
                $table->dropColumn(['first_name', 'middle_name', 'last_name', 'company_name', 'company_phone', 'company_email', 'notes']);
                $table->string('name')->after('id');
                $table->string('company')->nullable()->after('name');
                $table->string('industry')->nullable()->after('company');
                $table->string('state')->nullable()->after('city');
            }
        });
    }

    public function down(): void {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['name', 'company', 'industry', 'state']);
            $table->string('first_name')->after('id');
            $table->string('middle_name')->nullable()->after('first_name');
            $table->string('last_name')->after('middle_name');
            $table->string('company_name')->nullable()->after('company');
            $table->string('company_phone')->nullable()->after('company_name');
            $table->string('company_email')->nullable()->after('company_phone');
            $table->text('notes')->nullable()->after('country');
        });
    }
};