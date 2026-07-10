<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('name')->unique()->after('id');
            $table->string('guard_name')->default('web')->after('name');
            $table->string('display_name')->nullable()->after('guard_name');
            $table->string('description')->nullable()->after('display_name');
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->string('name')->unique()->after('id');
            $table->string('guard_name')->default('web')->after('name');
            $table->string('display_name')->nullable()->after('guard_name');
            $table->string('description')->nullable()->after('display_name');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['name', 'guard_name', 'display_name', 'description']);
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn(['name', 'guard_name', 'display_name', 'description']);
        });
    }
};