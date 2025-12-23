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
            $table->unsignedBigInteger('domain_id')->index();
            $table->string('target_url');
            $table->string('campaign_name');
            $table->string('title');
            $table->text('description');
            $table->string('banner_image')->nullable();
            $table->string('banner_icon')->nullable();
            $table->enum('schedule_type', ['instant', 'schedule'])->default('instant');
            $table->dateTime('one_time_datetime')->nullable()->index();
            $table->string('btn_1_title')->nullable();
            $table->string('btn_1_url')->nullable();    
            $table->string('btn_title_2')->nullable();
            $table->string('btn_url_2')->nullable();
            $table->string('message_id')->index();
            $table->unsignedInteger('active_count')->default(0);
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedInteger('chunks_total')->default(0);
            $table->unsignedInteger('chunks_done')->default(0);
            $table->foreignId('segment_id')->nullable()->constrained('segments')->nullOnDelete();
            $table->enum('segment_type', ['all', 'particular', 'api', 'rss', 'migrate'])->default('all');
            $table->enum('status', ['pending', 'queued', 'sent', 'failed','cancelled', 'processing'])->default('pending')->index();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->foreign('domain_id')->references('id')->on('domains')->onDelete('cascade');

            // INDEX
            $table->index(['status', 'schedule_type', 'one_time_datetime'], 'idx_notif_due');
            $table->index(['domain_id', 'status', 'created_at'], 'idx_notif_domain_status_created');
            $table->index(['domain_id', 'sent_at'], 'idx_notif_domain_sent');
            $table->index(['domain_id', 'segment_type'], 'idx_notif_domain_segment');
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
