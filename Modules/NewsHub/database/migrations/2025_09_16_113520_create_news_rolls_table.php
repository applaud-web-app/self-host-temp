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
        Schema::create('news_rolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained()->onDelete('cascade');
            $table->string('feed_url');
            $table->string('title');
            $table->string('icon')->nullable();
            $table->string('theme_color')->default('#fd683e');
            $table->enum('widget_placement', ['bottom-left', 'bottom-right'])->default('bottom-right');
            $table->boolean('show_on_desktop')->default(true);
            $table->boolean('show_on_mobile')->default(true);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news_rolls');
    }
};
