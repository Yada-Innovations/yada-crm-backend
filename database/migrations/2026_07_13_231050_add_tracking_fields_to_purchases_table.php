<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->date('expected_delivery_date')->nullable()->after('purchase_date');
            $table->date('reminder_date')->nullable()->after('expected_delivery_date');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['expected_delivery_date', 'reminder_date']);
        });
    }
};