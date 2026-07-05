<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'issue_date')) {
                $table->date('issue_date')->nullable()->after('invoice_number');
            }
            if (!Schema::hasColumn('invoices', 'tax')) {
                $table->decimal('tax', 15, 2)->default(0)->after('subtotal');
            }
            if (!Schema::hasColumn('invoices', 'tax_rate')) {
                $table->decimal('tax_rate', 5, 2)->default(16)->after('tax');
            }
            if (!Schema::hasColumn('invoices', 'notes')) {
                $table->text('notes')->nullable()->after('status');
            }
            if (!Schema::hasColumn('invoices', 'quote_id')) {
                $table->uuid('quote_id')->nullable()->after('client_id');
            }
        });
    }

    public function down(): void {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['issue_date', 'tax', 'tax_rate', 'notes', 'quote_id']);
        });
    }
};