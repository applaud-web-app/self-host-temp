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
        Schema::create('rss_feeds', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255); 
            $table->string('url', 255);
            $table->enum('type', ['latest', 'random'])->default('latest');
            $table->integer('random_count')->nullable();
            $table->time('start_time'); 
            $table->time('end_time');
            $table->integer('interval_min')->unsigned();
            $table->string('icon', 255)->nullable();
            $table->boolean('cta_enabled')->default(false);
            $table->string('button1_title', 255)->nullable();
            $table->string('button1_url', 255)->nullable();
            $table->string('button2_title', 255)->nullable();
            $table->string('button2_url', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Adding indexes inside the table creation block
            $table->index(['url']); 
            $table->index('start_time');
            $table->index('end_time');
            $table->index('interval_min');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rss_feeds');
    }
};
