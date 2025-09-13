<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('rss_feed_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rss_feed_id')->constrained('rss_feeds')->onDelete('cascade')->index(); // Link to RSS Feed
            $table->timestamp('last_sent_at')->nullable(); // When the notification was sent
            $table->timestamps();
            
            $table->unique(['rss_feed_id', 'last_sent_at'], 'rfn_feed_sent_unique');
            $table->index('last_sent_at', 'rfn_last_sent_at_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('rss_feed_notifications');
    }
};
