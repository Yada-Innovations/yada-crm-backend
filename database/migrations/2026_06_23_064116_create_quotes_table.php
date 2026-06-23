<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('quotes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('lead_id');
            $table->foreign('lead_id')->references('id')->on('leads')->cascadeOnDelete();
            $table->decimal('base_amount', 15, 2);
            $table->decimal('discount_pct', 5, 2)->default(0);
            $table->decimal('final_amount', 15, 2);
            $table->decimal('margin_pct', 5, 2); // never below 50
            $table->string('status')->default('draft'); // draft, sent, accepted, rejected
            $table->foreignId('created_by')->constrained('users');
            $table->date('valid_until')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('quotes'); }
};