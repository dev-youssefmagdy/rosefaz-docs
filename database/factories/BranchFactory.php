<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Branch> */
class BranchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'فرع '.fake()->city(),
            'number' => fake()->unique()->numberBetween(1, 100000),
            'is_active' => true,
        ];
    }
}
