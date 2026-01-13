<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = Category::all();

        if ($categories->isEmpty()) {
            $this->command->info('Tidak ada kategori. Jalankan CategorySeeder terlebih dahulu.');
            return;
        }

        // Disable foreign key checks (works for MySQL, PostgreSQL, SQLite)
        Schema::disableForeignKeyConstraints();

        // Clear existing products
        Product::truncate();

        // Re-enable foreign key checks
        Schema::enableForeignKeyConstraints();

        // Ensure the storage link exists
        $publicPath = public_path('storage/products');
        if (!file_exists($publicPath)) {

            $storagePath = storage_path('app/public/products');
            if (!file_exists($storagePath)) {
                mkdir($storagePath, 0755, true);
            }

            // Create symlink if it doesn't exist
            try {
                symlink($storagePath, $publicPath);
            } catch (\Exception $e) {
                $this->command->warn('Could not create symlink: ' . $e->getMessage());
            }
        }

        // Create 10 products total
        $productsPerCategory = min(10, $categories->count());
        $productsPerCategory = floor(10 / $productsPerCategory);

        $created = 0;
        foreach ($categories as $category) {
            $toCreate = min($productsPerCategory, 10 - $created);
            if ($toCreate <= 0) {
                break;
            }

            Product::factory($toCreate)->create([
                'category_id' => $category->id,
            ]);

            $created += $toCreate;
        }

        // If we still have less than 10 products, create the remaining ones
        if ($created < 10) {
            Product::factory(10 - $created)->create([
                'category_id' => $categories->random()->id
            ]);
        }
    }
}
