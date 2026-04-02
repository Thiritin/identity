<?php

namespace Database\Factories;

use App\Models\AppCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppCategoryFactory extends Factory
{
    protected $model = AppCategory::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'sort_order' => $this->faker->numberBetween(0, 100),
        ];
    }
}
