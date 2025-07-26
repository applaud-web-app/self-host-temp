<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
    {
        Schema::create('custom_prompts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('domain_id'); // Foreign key to domain table
            $table->string('title');
            $table->string('description')->nullable();
            $table->string('icon');
            $table->string('allow_btn_text');
            $table->string('allow_btn_color');
            $table->string('allow_btn_text_color');
            $table->string('deny_btn_text');
            $table->string('deny_btn_color');
            $table->string('deny_btn_text_color');
            $table->boolean('enable_desktop')->default(false);
            $table->boolean('enable_mobile')->default(false);
            $table->integer('delay')->default(0); // in seconds
            $table->integer('reappear')->default(0); // in seconds
            $table->enum('status', ['active', 'inactive']);
            $table->timestamps();

            // Adding the foreign key constraint
            $table->foreign('domain_id')->references('id')->on('domains')->onDelete('cascade');

            // Adding an index on the domain_id for performance optimization
            $table->index('domain_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('custom_prompts');
    }
};
