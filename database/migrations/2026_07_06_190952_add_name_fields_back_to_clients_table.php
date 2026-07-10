<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Add name fields if they don't exist
            if (!Schema::hasColumn('clients', 'first_name')) {
                $table->string('first_name')->nullable()->after('id');
            }
            
            if (!Schema::hasColumn('clients', 'middle_name')) {
                $table->string('middle_name')->nullable()->after('first_name');
            }
            
            if (!Schema::hasColumn('clients', 'last_name')) {
                $table->string('last_name')->nullable()->after('middle_name');
            }
            
            if (!Schema::hasColumn('clients', 'full_name')) {
                $table->string('full_name')->nullable()->after('last_name');
            }
            
            // Keep the existing 'name' column for backward compatibility
            // We'll use full_name as the main display name
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'middle_name', 'last_name', 'full_name']);
        });
    }
};