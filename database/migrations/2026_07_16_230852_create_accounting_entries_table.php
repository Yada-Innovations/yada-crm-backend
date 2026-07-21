<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('type', ['revenue', 'cost']);
            $table->string('category')->nullable(); // e.g. "Consulting", "Office Rent"
            $table->string('description');
            $table->decimal('amount', 14, 2);
            $table->date('entry_date');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_entries');
    }
};