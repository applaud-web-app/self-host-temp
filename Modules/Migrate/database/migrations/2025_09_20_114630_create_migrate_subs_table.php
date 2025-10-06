<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('migrate_subs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained('domains')->onDelete('cascade');
            $table->text('endpoint');
            $table->text('public_key');
            $table->text('private_key');
            $table->text('auth');
            $table->text('p256dh');
            $table->string('ip_address', 255)->nullable();
            $table->enum('migration_status', ['pending', 'migrated', 'failed'])->default('pending');
            $table->string('migrate_from', 50); // NEW COLUMN
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
            $table->index(['status', 'domain_id'], 'status_domain_idx');
            $table->index(['migration_status', 'domain_id'], 'migrate_subs_status_domain_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('migrate_subs');
    }
};
