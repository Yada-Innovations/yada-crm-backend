<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('invoice_number')->unique();
            $table->uuid('client_id');
            $table->foreign('client_id')->references('id')->on('clients')->cascadeOnDelete();
            $table->decimal('subtotal', 15, 2);
            $table->decimal('discount_pct', 5, 2)->default(0);
            $table->decimal('total', 15, 2);
            $table->decimal('margin_pct', 5, 2); // enforced >= 50
            $table->string('status')->default('draft'); // draft, sent, paid, overdue
            $table->string('etims_status')->default('pending'); // pending, synced, failed
            $table->string('etims_code')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->date('due_date')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('invoices'); }
};