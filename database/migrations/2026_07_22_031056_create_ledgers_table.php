<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ledgers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('chart_of_account_id');
            $table->uuid('transaction_group_id');
            $table->enum('type', ['debit', 'credit']);
            $table->decimal('amount', 14, 2);
            $table->date('entry_date');
            $table->string('reference_type')->nullable();
            $table->uuid('reference_id')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('chart_of_account_id')->references('id')->on('chart_of_accounts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledgers');
    }
};
