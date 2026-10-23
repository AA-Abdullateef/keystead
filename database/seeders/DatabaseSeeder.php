<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** Reference data only. Demo data is opt-in: `php artisan db:seed --class=DemoSeeder`. */
    public function run(): void
    {
        $this->call([RoleSeeder::class, CountrySeeder::class, StateSeeder::class]);
    }
}
