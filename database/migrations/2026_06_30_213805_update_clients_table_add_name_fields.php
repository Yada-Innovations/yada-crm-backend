<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('clients', function (Blueprint $table) {
            // Rename 'name' to 'full_name' temporarily
            $table->renameColumn('name', 'full_name');
            
            // Add new columns
            $table->string('first_name')->after('id');
            $table->string('middle_name')->nullable()->after('first_name');
            $table->string('last_name')->after('middle_name');
            $table->string('company_name')->nullable()->after('company');
            $table->string('company_phone')->nullable()->after('company_name');
            $table->string('company_email')->nullable()->after('company_phone');
            $table->string('address')->nullable()->after('status');
            $table->string('city')->nullable()->after('address');
            $table->string('country')->default('Kenya')->after('city');
            $table->text('notes')->nullable()->after('country');
        });
    }

    public function down(): void {
        Schema::table('clients', function (Blueprint $table) {
            $table->renameColumn('full_name', 'name');
            $table->dropColumn([
                'first_name',
                'middle_name',
                'last_name',
                'company_name',
                'company_phone',
                'company_email',
                'address',
                'city',
                'country',
                'notes'
            ]);
        });
    }
};