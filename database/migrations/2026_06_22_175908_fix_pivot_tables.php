<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('role_user', 'model_has_roles');
        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->after('id');
            $table->string('model_type')->default('App\\\Models\\\User')->after('role_id');
            $table->unsignedBigInteger('model_id')->after('model_type');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->index(['model_id', 'model_type']);
        });

        Schema::rename('permission_role', 'role_has_permissions');
        Schema::table('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id')->after('id');
            $table->unsignedBigInteger('role_id')->after('permission_id');
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
        });

        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type']);
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_has_permissions');

        Schema::table('role_has_permissions', function (Blueprint $table) {
            $table->dropForeign(['permission_id']);
            $table->dropForeign(['role_id']);
            $table->dropColumn(['permission_id', 'role_id']);
        });
        Schema::rename('role_has_permissions', 'permission_role');

        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropIndex(['model_id', 'model_type']);
            $table->dropColumn(['role_id', 'model_type', 'model_id']);
        });
        Schema::rename('model_has_roles', 'role_user');
    }
};