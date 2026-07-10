<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('quotes', function (Blueprint $table) {
            // Add client_id if it doesn't exist
            if (!Schema::hasColumn('quotes', 'client_id')) {
                $table->uuid('client_id')->nullable()->after('id');
                $table->foreign('client_id')->references('id')->on('clients')->nullOnDelete();
            }
            
            // Add service_id if it doesn't exist
            if (!Schema::hasColumn('quotes', 'service_id')) {
                $table->uuid('service_id')->nullable()->after('client_id');
                $table->foreign('service_id')->references('id')->on('services')->nullOnDelete();
            }
            
            // Add invoice_id if it doesn't exist
            if (!Schema::hasColumn('quotes', 'invoice_id')) {
                $table->uuid('invoice_id')->nullable()->after('status');
                $table->foreign('invoice_id')->references('id')->on('invoices')->nullOnDelete();
            }
            
            // Add approved_at if it doesn't exist
            if (!Schema::hasColumn('quotes', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('status');
            }
            
            // Add approved_by if it doesn't exist
            if (!Schema::hasColumn('quotes', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            }
            
            // Add is_editable column
            if (!Schema::hasColumn('quotes', 'is_editable')) {
                $table->boolean('is_editable')->default(true)->after('status');
            }
        });
    }

    public function down(): void {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropForeign(['service_id']);
            $table->dropForeign(['invoice_id']);
            $table->dropForeign(['approved_by']);
            
            $table->dropColumn([
                'client_id', 'service_id', 'invoice_id', 
                'approved_at', 'approved_by', 'is_editable'
            ]);
        });
    }
};