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
            $table->date('recurring_start_date')->nullable();
            $table->date('recurring_end_date')->nullable();
            $table->enum('occurrence', ['daily', 'weekly', 'monthly'])->nullable();
            $table->time('recurring_start_time')->nullable();
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
