<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateEmailSettingTable extends Migration
{
    public function up()
    {
        Schema::create('email_setting', function (Blueprint $table) {
            $table->id();
            $table->string('mail_driver')->default(config('mail.default'));
            $table->string('mail_host')->nullable();
            $table->unsignedSmallInteger('mail_port')->nullable();
            $table->string('mail_username')->nullable();
            $table->string('mail_password')->nullable();
            $table->string('mail_encryption')->nullable();
            $table->string('mail_from_address')->nullable();
            $table->string('mail_from_name')->nullable();
            $table->timestamps();
        });

        // seed a default row so id=1 always exists
        DB::table('email_setting')->insert([
            'mail_driver'        => config('mail.default'),
            'mail_host'          => config('mail.mailers.smtp.host'),
            'mail_port'          => config('mail.mailers.smtp.port'),
            'mail_username'      => config('mail.mailers.smtp.username'),
            'mail_password'      => config('mail.mailers.smtp.password'),
            'mail_encryption'    => config('mail.mailers.smtp.encryption'),
            'mail_from_address'  => config('mail.from.address'),
            'mail_from_name'     => config('mail.from.name'),
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('email_setting');
    }
}
