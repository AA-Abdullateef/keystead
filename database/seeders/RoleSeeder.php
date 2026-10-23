<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'admin' => 'Full access, including money-moving operations',
            'manager' => 'Back-office: properties, loan products, income and expenses (read-only on money movement)',
            'investor' => 'Customer account (API)',
        ] as $name => $description) {
            Role::firstOrCreate(['name' => $name], ['description' => $description]);
        }
    }
}
