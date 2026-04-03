<?php

namespace Database\Factories;

use App\Models\Convention;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Convention>
 */
class ConventionFactory extends Factory
{
    public function definition(): array
    {
        $year = $this->faker->numberBetween(1995, now()->year);

        return [
            'name' => "EuroFurence {$year}",
            'year' => $year,
        ];
    }
}
