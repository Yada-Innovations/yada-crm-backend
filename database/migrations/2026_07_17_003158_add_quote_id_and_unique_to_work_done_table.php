<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add the quote_id column if it doesn't exist
        if (!Schema::hasColumn('work_done', 'quote_id')) {
            Schema::table('work_done', function (Blueprint $table) {
                $table->uuid('quote_id')->nullable()->index()->after('id');
            });
        }

        // 2. Remove any duplicate quote_id records (keep the latest one)
        // This prevents the unique constraint from failing
        DB::statement('
            DELETE t1 FROM work_done t1
            INNER JOIN work_done t2 
            WHERE t1.id < t2.id 
            AND t1.quote_id = t2.quote_id
            AND t1.quote_id IS NOT NULL
        ');

        // 3. Add the unique constraint
        Schema::table('work_done', function (Blueprint $table) {
            $table->unique('quote_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_done', function (Blueprint $table) {
            $table->dropUnique(['quote_id']);
        });

        // Optionally drop the column if you want to rollback completely
        // But only drop if we added it (which we can't detect safely), so we skip.
    }
};