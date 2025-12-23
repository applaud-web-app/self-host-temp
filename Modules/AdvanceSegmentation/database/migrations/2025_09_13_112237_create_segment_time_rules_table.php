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
        Schema::create('segment_time_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('segment_id')->index();
            $table->foreign('segment_id', 'fk_str_segment')->references('id')->on('segments')->onDelete('cascade');
            $table->timestamp('start_at')->useCurrent();
            $table->timestamp('end_at')->useCurrent();
            $table->index(['start_at', 'end_at'], 'idx_str_window');
            $table->index(['segment_id', 'start_at', 'end_at'], 'idx_str_segment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('segment_time_rules');
    }
};
