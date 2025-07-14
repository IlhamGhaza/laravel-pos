<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = ['Pupuk Urea', 'Pupuk NPK', 'Pupuk Organik', 'Pestisida', 'Herbisida', 'Benih Padi', 'Benih Jagung'];

        foreach ($categories as $categoryName) {
            Category::factory()->create(['name' => $categoryName]);
        }

        // Category::factory(3)->create(); // Membuat 3 kategori tambahan dengan nama acak
    }
}
