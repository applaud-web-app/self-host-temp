<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('push_configs', function (Blueprint $table) {
            $table->id();
            $table->longText('web_app_config');
            $table->text('service_account_json');
            $table->string('vapid_public_key')->nullable();
            $table->text('vapid_private_key')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_configs');
    }
};
