<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasColumn('services', 'profit_margin')) {
                $table->decimal('profit_margin', 5, 2)->default(50)->after('tax_rate');
            }
        });
    }

    public function down(): void {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('profit_margin');
        });
    }
};