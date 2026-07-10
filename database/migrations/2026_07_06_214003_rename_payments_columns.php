<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Rename method to payment_method
            if (Schema::hasColumn('payments', 'method') && !Schema::hasColumn('payments', 'payment_method')) {
                $table->renameColumn('method', 'payment_method');
            }
            
            // Rename paid_at to payment_date
            if (Schema::hasColumn('payments', 'paid_at') && !Schema::hasColumn('payments', 'payment_date')) {
                $table->renameColumn('paid_at', 'payment_date');
            }
            
            // Add notes if it doesn't exist
            if (!Schema::hasColumn('payments', 'notes')) {
                $table->text('notes')->nullable()->after('reference');
            }
            
            // Add created_by if it doesn't exist
            if (!Schema::hasColumn('payments', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('payment_date');
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Rename back
            if (Schema::hasColumn('payments', 'payment_method') && !Schema::hasColumn('payments', 'method')) {
                $table->renameColumn('payment_method', 'method');
            }
            
            if (Schema::hasColumn('payments', 'payment_date') && !Schema::hasColumn('payments', 'paid_at')) {
                $table->renameColumn('payment_date', 'paid_at');
            }
            
            if (Schema::hasColumn('payments', 'notes')) {
                $table->dropColumn('notes');
            }
            
            if (Schema::hasColumn('payments', 'created_by')) {
                try {
                    $table->dropForeign(['created_by']);
                } catch (\Exception $e) {}
                $table->dropColumn('created_by');
            }
        });
    }
};