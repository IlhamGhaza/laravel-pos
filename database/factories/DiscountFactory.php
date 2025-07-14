<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Discount;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Discount>
 */
class DiscountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Discount::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(['fixed', 'percentage', 'buy_x_get_y', 'quantity_based', 'bulk_discount']);
        $value = 0;
        $buyQuantity = null;
        $getQuantity = null;
        $quantityTiers = null;
        $minQuantity = null;

        switch ($type) {
            case 'fixed':
                $value = $this->faker->numberBetween(1000, 50000); // Example fixed amount
                break;
            case 'percentage':
                $value = $this->faker->randomFloat(2, 1, 50); // Example percentage
                break;
            case 'buy_x_get_y':
                $buyQuantity = $this->faker->numberBetween(1, 10);
                $getQuantity = $this->faker->numberBetween(1, 5);
                break;
            case 'quantity_based':
                $minQuantity = $this->faker->numberBetween(5, 20);
                $value = $this->faker->randomFloat(2, 1, 20); // Discount percentage or fixed amount per item
                break;
            case 'bulk_discount':
                $quantityTiers = [
                    ['min_qty' => $this->faker->numberBetween(10, 20), 'max_qty' => $this->faker->numberBetween(21, 50), 'discount' => $this->faker->randomFloat(2, 1, 5)],
                    ['min_qty' => $this->faker->numberBetween(51, 100), 'max_qty' => null, 'discount' => $this->faker->randomFloat(2, 5, 10)],
                ];
                break;
        }

        $applyTo = $this->faker->randomElement(['all', 'category', 'product']);
        $applicableItems = null;
        if ($applyTo !== 'all') {
            // Assuming you have Category and Product factories or existing IDs
            // For simplicity, let's use random numbers. Replace with actual logic if needed.
            $applicableItems = ($applyTo === 'category') ?
                json_encode($this->faker->randomElements([1, 2, 3, 4, 5], $this->faker->numberBetween(1, 3))) :
                json_encode($this->faker->randomElements([101, 102, 103, 104, 105], $this->faker->numberBetween(1, 3)));
        }


        return [
            'name' => $this->faker->words(3, true) . ' Discount',
            'description' => $this->faker->sentence,
            'type' => $type,
            'value' => $value,
            'min_quantity' => $minQuantity,
            'max_quantity' => $type === 'quantity_based' ? $this->faker->optional()->numberBetween(21, 50) : null,
            'min_amount' => $this->faker->optional()->randomFloat(2, 50000, 1000000),
            'buy_quantity' => $buyQuantity,
            'get_quantity' => $getQuantity,
            'quantity_tiers' => $quantityTiers ? json_encode($quantityTiers) : null,
            'apply_to' => $applyTo,
            'applicable_items' => $applicableItems,
            'customer_type' => $this->faker->randomElement(['all', 'retail', 'wholesale', 'member']),
            'combinable' => $this->faker->boolean,
            'usage_limit' => $this->faker->optional()->numberBetween(10, 1000),
            'usage_count' => 0,
            'status' => $this->faker->randomElement(['active', 'inactive']),
            'start_date' => $this->faker->optional(0.7, null)->dateTimeBetween('-1 month', '+1 month')?->format('Y-m-d'),
            'expired_date' => $this->faker->optional(0.7, null)->dateTimeBetween('+1 month', '+6 months')?->format('Y-m-d'),
            'start_time' => $this->faker->optional()->time('H:i:s'),
            'end_time' => $this->faker->optional()->time('H:i:s'),
        ];
    }
}
