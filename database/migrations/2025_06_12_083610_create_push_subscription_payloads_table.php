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
        Schema::create('push_subscriptions_payload', function (Blueprint $table) {
            $table->unsignedBigInteger('head_id');
            $table->text('endpoint');
            $table->string('auth');
            $table->string('p256dh');

            // set head_id as primary key
            $table->primary('head_id');

            $table->foreign('head_id')->references('id')->on('push_subscriptions_head')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions_payload');
    }
};
