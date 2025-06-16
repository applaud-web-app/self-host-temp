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
        Schema::create('notifications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('target_url');
            $table->string('campaign_name');
            $table->string('title');
            $table->text('description');
            $table->string('banner_image')->nullable();
            $table->string('banner_icon')->nullable();
            $table->enum('schedule_type', ['instant', 'schedule'])->default('instant');
            $table->dateTime('one_time_datetime')->nullable();
            $table->string('btn_1_title')->nullable();
            $table->string('btn_1_url')->nullable();
            $table->string('btn_title_2')->nullable();
            $table->string('btn_url_2')->nullable();
            $table->string('message_id');
            $table->unsignedBigInteger('active_count')->default(0);
            $table->unsignedBigInteger('success_count')->default(0);
            $table->unsignedBigInteger('failed_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
