<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Discount;
use App\Models\ValidDay;
use Carbon\Carbon;

class DiscountSeeder extends Seeder
{
    public function run(): void
    {
        Discount::factory()->count(25)->create();
        // 1. Diskon Member (berlaku setiap hari)
        $discount1 = Discount::create([
            'name' => 'Diskon Member 5%',
            'description' => 'Diskon khusus untuk member terdaftar',
            'type' => 'percentage',
            'value' => 5.00,
            'apply_to' => 'all',
            'customer_type' => 'member',
            'combinable' => true,
            'status' => 'active',
            'start_date' => Carbon::now()->subDays(10),
            'expired_date' => Carbon::now()->addMonths(6),
        ]);

        // 2. Diskon Pagi Hari (Senin-Sabtu)
        $discount2 = Discount::create([
            'name' => 'Diskon Pagi Hari',
            'description' => 'Diskon khusus transaksi pagi jam 6-10',
            'type' => 'fixed',
            'value' => 25000.00,
            'min_amount' => 200000.00,
            'apply_to' => 'all',
            'customer_type' => 'all',
            'combinable' => true,
            'start_time' => '06:00:00',
            'end_time' => '10:00:00',
            'status' => 'active',
            'start_date' => Carbon::now(),
            'expired_date' => Carbon::now()->addMonths(2),
        ]);

        // Valid days untuk diskon pagi (Senin-Sabtu)
        foreach ([1, 2, 3, 4, 5, 6] as $day) {
            ValidDay::create([
                'discount_id' => $discount2->id,
                'day_of_week' => $day
            ]);
        }

        // 3. Diskon Weekend (Sabtu-Minggu)
        $discount3 = Discount::create([
            'name' => 'Diskon Weekend 10%',
            'description' => 'Diskon khusus akhir pekan',
            'type' => 'percentage',
            'value' => 10.00,
            'min_amount' => 100000.00,
            'apply_to' => 'all',
            'customer_type' => 'retail',
            'combinable' => false,
            'usage_limit' => 50,
            'usage_count' => 8,
            'status' => 'active',
            'start_date' => Carbon::now()->subWeek(),
            'expired_date' => Carbon::now()->addMonth(),
        ]);

        // Valid days untuk weekend (Sabtu-Minggu)
        foreach ([6, 7] as $day) {
            ValidDay::create([
                'discount_id' => $discount3->id,
                'day_of_week' => $day
            ]);
        }

        // 4. Diskon Weekdays (Senin-Jumat)
        $discount4 = Discount::create([
            'name' => 'Diskon VIP Weekdays',
            'description' => 'Diskon VIP khusus hari kerja',
            'type' => 'percentage',
            'value' => 8.00,
            'min_quantity' => 30.00,
            'min_amount' => 750000.00,
            'apply_to' => 'all',
            'customer_type' => 'member',
            'combinable' => true,
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'usage_limit' => 25,
            'usage_count' => 3,
            'status' => 'active',
            'start_date' => Carbon::now(),
            'expired_date' => Carbon::now()->addMonths(4),
        ]);

        // Valid days untuk weekdays (Senin-Jumat)
        foreach ([1, 2, 3, 4, 5] as $day) {
            ValidDay::create([
                'discount_id' => $discount4->id,
                'day_of_week' => $day
            ]);
        }

        // 5. Diskon tanpa pembatasan hari (berlaku setiap hari)
        Discount::create([
            'name' => 'Diskon Grosir 50kg+',
            'description' => 'Diskon 7% untuk pembelian minimal 50kg',
            'type' => 'quantity_based',
            'value' => 7.00,
            'min_quantity' => 50.00,
            'max_quantity' => 199.99,
            'apply_to' => 'all',
            'customer_type' => 'wholesale',
            'combinable' => true,
            'status' => 'active',
            'start_date' => Carbon::now()->subMonth(),
            'expired_date' => null,
        ]);
    }
}
