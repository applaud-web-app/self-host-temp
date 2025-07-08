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
        Schema::create('push_subscriptions_meta', function (Blueprint $table) {
            $table->unsignedBigInteger('head_id');
            $table->text('subscribed_url')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('country')->nullable();
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->string('device')->nullable();
            $table->string('browser')->nullable();
            $table->string('platform')->nullable();

            $table->primary('head_id');

            $table->foreign('head_id')
                  ->references('id')
                  ->on('push_subscriptions_head')
                  ->onDelete('cascade');

            // indexes for segmentation
            $table->index('country');
            $table->index('state');
            $table->index('city');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions_meta');
    }
};
