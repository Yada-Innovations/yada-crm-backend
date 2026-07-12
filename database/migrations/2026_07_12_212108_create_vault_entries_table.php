<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vault_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');                     // e.g. "Acme Corp Website"
            $table->string('category')->default('other'); // vps, domain, hosting, database, api_key, email, social_media, other
            $table->string('client_name')->nullable();    // free-text link to a client/project
            $table->string('website_url')->nullable();
            $table->string('vps_ip')->nullable();
            $table->string('vps_port')->nullable();
            $table->string('ssh_username')->nullable();
            $table->string('username')->nullable();
            $table->text('password')->nullable();  // encrypted at rest via model cast
            $table->text('api_key')->nullable();    // encrypted at rest via model cast
            $table->text('notes')->nullable();
            $table->json('extra_fields')->nullable(); // flexible key/value for anything else
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vault_entries');
    }
};