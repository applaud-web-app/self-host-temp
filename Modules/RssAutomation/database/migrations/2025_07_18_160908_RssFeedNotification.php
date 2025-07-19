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
            $table->foreignId('rss_feed_id')->constrained('rss_feeds')->onDelete('cascade'); // Link to RSS Feed
            $table->foreignId('notification_id')->constrained('notifications')->onDelete('cascade'); // Link to Notification
            $table->timestamp('last_sent_at')->nullable(); // When the notification was sent
            $table->timestamps();
            
            $table->index('rss_feed_id');
            $table->index('notification_id');
            $table->index('last_sent_at');
            $table->unique(['rss_feed_id', 'last_sent_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('rss_feed_notifications');
    }
};
