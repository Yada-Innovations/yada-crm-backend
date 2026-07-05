<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void {
        Schema::create('leads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Contact Information
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('title')->nullable();
            
            // Company Information
            $table->string('company');
            $table->string('industry')->nullable();
            $table->string('company_size')->nullable();
            $table->string('website')->nullable();
            
            // Address
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->default('Kenya');
            
            // Lead Status & Pipeline
            $table->string('status')->default('new'); // new, contacted, qualified, disqualified, converted
            $table->string('priority')->default('medium'); // high, medium, low
            $table->string('sales_stage')->default('prospecting');
            $table->integer('score')->default(0);
            
            // Financials
            $table->decimal('estimated_value', 15, 2)->default(0);
            $table->string('currency')->default('KES');
            $table->date('expected_close_date')->nullable();
            
            // Additional Info
            $table->text('notes')->nullable();
            $table->string('source')->nullable();
            
            // Assignment
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
        });
    }

    public function down(): void { 
        Schema::dropIfExists('leads'); 
    }
};