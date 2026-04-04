<?php

namespace Database\Factories;

use App\Enums\NotificationCategory;
use App\Models\App;
use App\Models\NotificationType;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationTypeFactory extends Factory
{
    protected $model = NotificationType::class;

    public function definition(): array
    {
        return [
            'app_id' => App::factory(),
            'key' => fake()->unique()->slug(2, false),
            'name' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'category' => NotificationCategory::Operational,
            'default_channels' => ['email', 'database'],
            'disabled' => false,
        ];
    }

    public function transactional(): static
    {
        return $this->state([
            'category' => NotificationCategory::Transactional,
            'default_channels' => ['email'],
        ]);
    }
}
