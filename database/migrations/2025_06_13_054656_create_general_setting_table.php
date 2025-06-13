<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateGeneralSettingTable extends Migration
{
    public function up()
    {
        Schema::create('general_setting', function (Blueprint $table) {
            $table->id();
            $table->string('site_name');
            $table->string('site_url');
            $table->string('site_tagline')->nullable();
            $table->timestamps();
        });

        // Seed one default row (id = 1)
        DB::table('general_setting')->insert([
            'site_name'    => config('app.name', 'My Site'),
            'site_url'     => url('/'),
            'site_tagline' => config('app.tagline', ''),
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('general_setting');
    }
}
