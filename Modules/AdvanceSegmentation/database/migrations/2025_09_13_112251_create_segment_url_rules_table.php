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
        Schema::create('segment_url_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('segment_id')->index();
            $table->foreign('segment_id', 'fk_sur_segment')->references('id')->on('segments')->onDelete('cascade');
            $table->string('url', 2048);
            // $table->binary('url_hash', 32)->index('idx_sur_url_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('segment_url_rules');
    }
};
