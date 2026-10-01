<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(SettingSeeder::class);

        if (config('jhs.demo_credentials_enabled')) {
            $this->call(DemoSeeder::class);

            return;
        }

        $this->call(InitialAdminSeeder::class);
    }
}
