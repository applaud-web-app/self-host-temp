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
        Schema::table('notifications', function (Blueprint $table) {
            $table->enum('schedule_type', ['all', 'particular'])->default('all');
            $table->foreignId('segment_id')->nullable()->constrained('segments')->nullOnDelete()->after('id');
            $table->index(
                ['segment_type', 'one_time_datetime'],
                'notifications_segment_schedule_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('segment_id');
        });
    }
};
