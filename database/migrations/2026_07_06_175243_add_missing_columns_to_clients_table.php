<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('clients', function (Blueprint $table) {
            // Only add columns that don't already exist
            if (!Schema::hasColumn('clients', 'client_type')) {
                $table->string('client_type')->default('business')->after('status');
            }
            
            if (!Schema::hasColumn('clients', 'tax_id')) {
                $table->string('tax_id')->nullable()->after('client_type');
            }
            
            if (!Schema::hasColumn('clients', 'billing_address')) {
                $table->text('billing_address')->nullable()->after('tax_id');
            }
            
            if (!Schema::hasColumn('clients', 'shipping_address')) {
                $table->text('shipping_address')->nullable()->after('billing_address');
            }
            
            if (!Schema::hasColumn('clients', 'website')) {
                $table->string('website')->nullable()->after('shipping_address');
            }
            
            if (!Schema::hasColumn('clients', 'notes')) {
                $table->text('notes')->nullable()->after('website');
            }
        });
    }

    public function down(): void {
        Schema::table('clients', function (Blueprint $table) {
            $columns = ['client_type', 'tax_id', 'billing_address', 'shipping_address', 'website', 'notes'];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('clients', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};