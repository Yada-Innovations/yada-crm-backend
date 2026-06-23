<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name'); // Starter, Pro, Enterprise
            $table->decimal('price', 15, 2);
            $table->string('currency')->default('KES');
            $table->integer('max_seats');
            $table->string('billing_cycle')->default('monthly'); // monthly, annual
            $table->json('features')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('subscription_plans'); }
};