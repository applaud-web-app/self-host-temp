<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Setting;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
    */
    public function run(): void
    {
        Setting::updateOrCreate(
            ['id' => 1],
            [
                'batch_size'    => 500,
                'daily_cleanup' => 0
            ]
        );
    }
}
