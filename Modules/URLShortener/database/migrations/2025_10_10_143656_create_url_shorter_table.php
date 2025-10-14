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
        Schema::create('url_shorter', function (Blueprint $table) {
            $table->id();
            $table->string('domain')->default('default.com');
            $table->string('target_url')->unique();
            $table->string('short_url')->unique();
            $table->string('prompt',225)->nullable();
            $table->boolean('forced_subscribe')->default(false);
            $table->enum('type', ['yt', 'url'])->default('url');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('url_shorter');
    }
};
