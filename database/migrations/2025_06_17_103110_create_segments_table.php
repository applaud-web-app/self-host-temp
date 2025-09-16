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
        Schema::create('segments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('domain')->index();
            $table->enum('type', ['device', 'geo', 'time', 'url']);
            $table->tinyInteger('status')->default(1);
            $table->timestamps();

            $table->index(['status', 'type', 'created_at'], 'idx_segments_status_type_created');
            $table->index(['status', 'name'], 'idx_segments_status_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('segments');
    }
};
