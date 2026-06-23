<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('feature_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('description');
            $table->foreignId('submitted_by')->constrained('users');
            $table->string('status')->default('backlog'); // backlog, under_review, planned, completed
            $table->integer('votes')->default(0);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('feature_requests'); }
};