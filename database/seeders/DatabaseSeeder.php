<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            PlanSeeder::class,
            RouterSeeder::class,
        ]);

        User::factory()->create([
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'phone' => '1234567890',
            'password' => Hash::make('password'),
            'radius_password' => 'password123',
            'data_used' => 0,
            'data_limit' => 1000000000, // 1GB in bytes
            'connection_status' => 'active',
        ]);

        // Create a few more test users
        User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'username' => 'johndoe',
            'phone' => '0987654321',
            'data_used' => 500000000, // 500MB
            'data_limit' => 2000000000, // 2GB
            'connection_status' => 'active',
            'radius_password' => 'password123',
        ]);

        User::factory()->create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'username' => 'janesmith',
            'phone' => '1122334455',
            'data_used' => 0,
            'data_limit' => 500000000, // 500MB
            'connection_status' => 'inactive',
            'radius_password' => 'password123',
        ]);
    }
}
