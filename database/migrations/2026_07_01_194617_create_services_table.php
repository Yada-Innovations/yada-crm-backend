<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(16);
            $table->decimal('profit_margin', 5, 2)->default(50);
            $table->string('category')->nullable();
            $table->string('duration')->nullable();
            $table->string('delivery_time')->nullable();
            $table->json('features')->nullable();
            $table->string('status')->default('active');
            $table->boolean('is_available')->default(true);
            $table->boolean('requires_consultation')->default(false);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('status');
            $table->index('category');
        });
    }

    public function down(): void {
        Schema::dropIfExists('services');
    }
};