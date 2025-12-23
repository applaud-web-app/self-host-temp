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
        Schema::create('segment_geo_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('segment_id')->index();
            $table->foreign('segment_id', 'fk_sgr_segment')->references('id')->on('segments')->onDelete('cascade');
            $table->enum('operator', ['equals', 'not_equals']);
            $table->string('country');
            $table->string('state')->nullable();
            $table->index(['country', 'state']);
            $table->index(['segment_id', 'country', 'state'], 'idx_sgr_segment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('segment_geo_rules');
    }
};
