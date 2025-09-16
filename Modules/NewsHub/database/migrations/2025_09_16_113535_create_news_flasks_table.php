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
        Schema::create('news_flasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained()->onDelete('cascade');
            $table->string('feed_url');
            $table->string('title');
            $table->string('theme_color')->default('#fd683e');
            $table->boolean('exit_intent')->default(false);
            $table->integer('after_seconds')->nullable();
            $table->boolean('scroll_down')->default(false);
            $table->integer('show_again_after_minutes')->default(5);
            $table->boolean('enable_desktop')->default(true);
            $table->boolean('enable_mobile')->default(true);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news_flasks');
    }
};
