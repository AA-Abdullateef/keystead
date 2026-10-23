<?php

namespace Tests\Feature;

use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_data_builds_and_reconciles(): void
    {
        $this->seed(DemoSeeder::class);

        $this->assertDatabaseHas('users', ['email' => 'admin@keystead.test']);
        $this->artisan('finance:reconcile')->assertSuccessful();
        $this->seed(DemoSeeder::class); // idempotent
        $this->assertSame(1, \App\Models\User::where('email', 'admin@keystead.test')->count());
    }
}
