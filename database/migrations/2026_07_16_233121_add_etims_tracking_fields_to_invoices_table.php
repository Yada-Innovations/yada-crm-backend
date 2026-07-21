<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->text('etims_error')->nullable()->after('etims_code');
            $table->timestamp('etims_synced_at')->nullable()->after('etims_error');
            $table->unsignedTinyInteger('etims_attempts')->default(0)->after('etims_synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['etims_error', 'etims_synced_at', 'etims_attempts']);
        });
    }
};