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
        Schema::create('news_bottom_sliders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained()->onDelete('cascade');
            $table->string('feed_url', 2048);
            $table->string('theme_color', 16)->default('#000000');
            $table->enum('mode', ['latest', 'random'])->default('latest');
            $table->unsignedInteger('posts_count')->default(8);
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
        Schema::dropIfExists('news_bottom_sliders');
    }
};
