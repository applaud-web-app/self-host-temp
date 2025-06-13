<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBackupsubsTable extends Migration
{
    public function up()
    {
        Schema::create('backupsubs', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->unsignedInteger('count');
            $table->string('path');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('backupsubs');
    }
}
