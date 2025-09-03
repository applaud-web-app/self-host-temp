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
        Schema::create('push_subscriptions_head', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('token')->unique();
            $table->string('domain');
            $table->string('parent_origin')->index();
            $table->tinyInteger('status')->default(1)->index();
            $table->timestamps();

            $table->index(['parent_origin', 'status', 'id'], 'idx_psh_origin_status_id');
            $table->index(['created_at', 'parent_origin'], 'idx_psh_created_origin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions_head');
    }
};
