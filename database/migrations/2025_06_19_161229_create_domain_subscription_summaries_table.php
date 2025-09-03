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
        Schema::create('domain_subscription_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained()->onDelete('cascade');
            $table->date('stat_date')->index();
            $table->unsignedBigInteger('total_subscribers')->default(0);
            $table->unsignedBigInteger('monthly_subscribers')->default(0);
            $table->unsignedBigInteger('daily_subscribers')->default(0);
            $table->unsignedBigInteger('active_subscribers')->default(0);
            $table->timestamps();

            $table->unique(['domain_id', 'stat_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_subscription_summaries');
    }
};
