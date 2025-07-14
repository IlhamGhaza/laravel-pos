<?php

namespace Database\Factories;

use App\Models\ServiceCharge;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\service_charge>
 */
class ServiceChargeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ServiceCharge::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true) . ' Charge',
            'rate' => $this->faker->randomFloat(2, 1, 15),
        ];
    }
}
