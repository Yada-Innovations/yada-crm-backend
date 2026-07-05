<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('work_done', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Client and Lead Info
            $table->uuid('client_id')->nullable();
            $table->foreign('client_id')->references('id')->on('clients')->nullOnDelete();
            $table->uuid('lead_id')->nullable();
            $table->foreign('lead_id')->references('id')->on('leads')->nullOnDelete();
            
            // Work Details
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type')->default('development'); // development, design, consulting, support
            $table->string('priority')->default('medium'); // high, medium, low
            
            // Status
            $table->string('status')->default('pending'); // pending, in_progress, completed, approved, invoiced
            
            // Dates
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('completion_date')->nullable();
            
            // Time tracking
            $table->decimal('estimated_hours', 8, 2)->default(0);
            $table->decimal('actual_hours', 8, 2)->default(0);
            
            // Financials
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(16);
            $table->decimal('total_amount', 15, 2)->default(0);
            
            // Invoice reference
            $table->uuid('invoice_id')->nullable();
            $table->foreign('invoice_id')->references('id')->on('invoices')->nullOnDelete();
            
            // Additional
            $table->text('notes')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            
            // Indexes
            $table->index('status');
            $table->index('type');
            $table->index('client_id');
        });
    }

    public function down(): void {
        Schema::dropIfExists('work_done');
    }
};