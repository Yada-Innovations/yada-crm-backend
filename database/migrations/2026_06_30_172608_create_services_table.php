<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Basic Information
            $table->string('name');
            $table->text('description')->nullable();
            
            // Pricing
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(16);
            
            // Categorization
            $table->string('category')->nullable();
            $table->string('duration')->nullable(); // e.g., "2 weeks"
            $table->string('delivery_time')->nullable(); // e.g., "5 business days"
            
            // Features as JSON
            $table->json('features')->nullable();
            
            // Status & Availability
            $table->string('status')->default('active'); // active, inactive, draft
            $table->boolean('is_available')->default(true);
            $table->boolean('requires_consultation')->default(false);
            
            // Additional Info
            $table->text('notes')->nullable();
            
            // Tracking
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('services');
    }
};