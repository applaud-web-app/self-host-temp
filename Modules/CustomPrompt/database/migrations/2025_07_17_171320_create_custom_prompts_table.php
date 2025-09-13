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
            $table->string('title',100);
            $table->string('description',100)->nullable();
            $table->string('icon');
            $table->string('allow_btn_text',100);
            $table->string('allow_btn_color',100);
            $table->string('allow_btn_text_color',100);
            $table->string('deny_btn_text',100);
            $table->string('deny_btn_color',100);
            $table->string('deny_btn_text_color',100);
            $table->boolean('enable_desktop')->default(false);
            $table->boolean('enable_mobile')->default(false);
            $table->boolean('enable_allow_only')->default(false);
            $table->enum('prompt_location_mobile', ['top', 'bottom', 'center'])->default('bottom');
            $table->integer('delay')->default(0);
            $table->integer('reappear')->default(0);
            $table->tinyInteger('status')->default(0);
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
