<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $imageDirectory = storage_path('app/public/products');
        if (!File::isDirectory($imageDirectory)) {
            File::makeDirectory($imageDirectory, 0755, true, true);
        }

        // Generate a unique filename
        $filename = uniqid() . '.jpg';
        $fullPath = $imageDirectory . '/' . $filename;

        // Generate a placeholder image using Faker
        $image = $this->faker->image(
            dirname($fullPath), // Directory
            640,                // Width
            480,                // Height
            'products',         // Category
            false,              // Full path
            true,               // Randomize
            $this->faker->sentence(3) // Text on image
        );

        // If image generation failed, use a fallback
        if (!$image) {
            $imageName = 'products/default-product.jpg';
            // Make sure the default image exists
            if (!File::exists(public_path($imageName))) {
                File::ensureDirectoryExists(public_path('products'));
                File::copy(
                    base_path('vendor/fakerphp/faker/src/Faker/Provider/Image.php'),
                    public_path($imageName)
                );
            }
        } else {
            $imageName = 'products/' . basename($image);
        }
        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence,
            'price' => $this->faker->numberBetween(10000, 100000),
            'category_id' => Category::factory(),
            //faker image
            'image' => $imageName,
            'is_best_seller' => $this->faker->boolean(25),
            'isReady' => $this->faker->boolean(90),
            'sku' => $this->faker->unique()->ean8(),
            'unit_of_measure' => $this->faker->randomElement(['kg', 'pcs', 'liter', 'pack']),
            'expired_date' => ($date = $this->faker->optional(0.7)->dateTimeBetween('+1 month', '+2 years')) ? $date->format('Y-m-d') : null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
