<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('leads', function (Blueprint $table) {
            // Check if stage exists, rename to status
            if (Schema::hasColumn('leads', 'stage')) {
                $table->renameColumn('stage', 'status');
            }
            
            // Add missing columns (using company_name since your table has that)
            if (!Schema::hasColumn('leads', 'priority')) {
                $table->string('priority')->default('medium')->after('status');
            }
            
            if (!Schema::hasColumn('leads', 'sales_stage')) {
                $table->string('sales_stage')->default('prospecting')->after('priority');
            }
            
            if (!Schema::hasColumn('leads', 'score')) {
                $table->integer('score')->default(0)->after('sales_stage');
            }
            
            if (!Schema::hasColumn('leads', 'currency')) {
                $table->string('currency')->default('KES')->after('estimated_value');
            }
            
            if (!Schema::hasColumn('leads', 'title')) {
                $table->string('title')->nullable()->after('contact_name');
            }
            
            if (!Schema::hasColumn('leads', 'source')) {
                $table->string('source')->nullable()->after('status');
            }
            
            if (!Schema::hasColumn('leads', 'industry')) {
                $table->string('industry')->nullable()->after('company_name');
            }
            
            if (!Schema::hasColumn('leads', 'company_size')) {
                $table->string('company_size')->nullable()->after('industry');
            }
            
            if (!Schema::hasColumn('leads', 'website')) {
                $table->string('website')->nullable()->after('company_size');
            }
            
            if (!Schema::hasColumn('leads', 'address')) {
                $table->string('address')->nullable()->after('website');
            }
            
            if (!Schema::hasColumn('leads', 'city')) {
                $table->string('city')->nullable()->after('address');
            }
            
            if (!Schema::hasColumn('leads', 'state')) {
                $table->string('state')->nullable()->after('city');
            }
            
            if (!Schema::hasColumn('leads', 'country')) {
                $table->string('country')->default('Kenya')->after('state');
            }
            
            if (!Schema::hasColumn('leads', 'expected_close_date')) {
                $table->date('expected_close_date')->nullable()->after('estimated_value');
            }
        });
    }

    public function down(): void {
        Schema::table('leads', function (Blueprint $table) {
            if (Schema::hasColumn('leads', 'status')) {
                $table->renameColumn('status', 'stage');
            }
            
            $table->dropColumn([
                'priority',
                'sales_stage',
                'score',
                'currency',
                'title',
                'source',
                'industry',
                'company_size',
                'website',
                'address',
                'city',
                'state',
                'country',
                'expected_close_date'
            ]);
        });
    }
};