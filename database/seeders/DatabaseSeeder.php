<?php

namespace Database\Seeders;
use Illuminate\Support\Facades\Hash;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

       User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
         // ]);
        \App\Models\User::factory()->create([
            'name' => 'Test Ilham ',
            'email' => 'ilham@admin.com',
            'password' => Hash::make('12345678'),
        ]);
        \App\Models\User::factory()->create([
            'name' => 'Test user ',
            'email' => 'user@user.com',
            'password' => Hash::make('45678912'),
        ]);
    }
}
