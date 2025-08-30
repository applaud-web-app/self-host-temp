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
        Schema::create('push_event_counts', function (Blueprint $table) {
            // $table->id();
            // $table->string('message_id');
            // $table->string('domain');
            // $table->string('event');
            // $table->unsignedBigInteger('count')->default(0);
            // $table->unique(['message_id', 'event', 'domain']);
            // $table->index(['domain', 'message_id']);

            $table->string('message_id', 255);
            $table->string('event', 255);
            $table->unsignedBigInteger('count')->default(0);
            $table->primary(['message_id','event']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_event_counts');
    }
};
