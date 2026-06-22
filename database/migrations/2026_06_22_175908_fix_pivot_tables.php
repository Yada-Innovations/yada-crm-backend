<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fix role_user pivot table
        Schema::table('role_user', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->after('id');
            $table->unsignedBigInteger('user_id')->after('role_id');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // Fix permission_role pivot table
        Schema::table('permission_role', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id')->after('id');
            $table->unsignedBigInteger('role_id')->after('permission_id');
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('role_user', function (Blueprint $table) {
            $table->dropForeign(['role_id', 'user_id']);
            $table->dropColumn(['role_id', 'user_id']);
        });

        Schema::table('permission_role', function (Blueprint $table) {
            $table->dropForeign(['permission_id', 'role_id']);
            $table->dropColumn(['permission_id', 'role_id']);
        });
    }
};