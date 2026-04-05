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
        $startDate = $this->faker->dateTimeBetween("{$year}-06-01", "{$year}-09-30");
        $endDate = (clone $startDate)->modify('+4 days');

        return [
            'name' => "Eurofurence {$year}",
            'year' => $year,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'theme' => $this->faker->optional()->words(3, true),
            'location' => null,
            'website_url' => null,
            'conbook_url' => null,
            'attendees_count' => null,
            'background_image_path' => null,
            'dailies' => [],
            'videos' => [],
            'photos' => [],
        ];
    }
}
