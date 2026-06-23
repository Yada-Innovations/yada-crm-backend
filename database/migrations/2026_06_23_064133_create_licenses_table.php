<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('licenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('subscription_id');
            $table->foreign('subscription_id')->references('id')->on('subscriptions')->cascadeOnDelete();
            $table->string('license_key')->unique();
            $table->string('status')->default('active');
            $table->date('expires_at');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('licenses'); }
};