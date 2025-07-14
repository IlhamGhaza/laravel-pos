<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Supplier>
 */
class SupplierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
            'name' => $this->faker->name,
            'phone_number' => $this->faker->phoneNumber,
            'email' => $this->faker->email,
            'address' => $this->faker->address,
            'company_name' => $this->faker->company,
            'tax_number' => $this->faker->unique()->numerify('#########'),
            'payment_terms' => $this->faker->randomElement(['cash', 'credit']),
            'credit_days' => $this->faker->numberBetween(1, 30),
            'status' => $this->faker->randomElement(['active', 'inactive', 'suspended']),
            'notes' => $this->faker->text,
        ];
    }
}
