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
        Schema::create('domain_notification', function (Blueprint $table) {
            $table->unsignedBigInteger('notification_id');
            $table->unsignedBigInteger('domain_id');

            $table->foreign('notification_id')
                  ->references('id')->on('notifications')
                  ->onDelete('cascade');

            $table->foreign('domain_id')
                  ->references('id')->on('domains')
                  ->onDelete('cascade');

            $table->primary(['notification_id', 'domain_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_notification');
    }
};
