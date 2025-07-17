<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAddonsTable extends Migration
{
    public function up()
    {
        Schema::create('addons', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 100);
            $table->string('version', 20);
            $table->string('file_path');
            $table->unsignedInteger('file_size');
            $table->string('addon_key')->nullable();
            $table->enum('status', ['uploaded', 'installed'])->default('uploaded');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('addons');
    }
}
