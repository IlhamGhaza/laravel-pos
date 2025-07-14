<?php

namespace Database\Seeders;

use App\Models\ServiceCharge;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ServiceChargeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ServiceCharge::factory()->create([
            'name' => 'Standard Service Fee',
            'rate' => 5.00,
            // 'is_active' => true,
        ]);
    }
}
