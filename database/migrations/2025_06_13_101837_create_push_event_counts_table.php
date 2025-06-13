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
            $table->id();
            $table->string('message_id')->index();
            $table->string('event')->index();
            $table->unsignedBigInteger('count')->default(0);
            $table->unique(['message_id', 'event']);
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
