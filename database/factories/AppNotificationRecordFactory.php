<?php

namespace Database\Factories;

use App\Models\AppNotificationRecord;
use App\Models\NotificationType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppNotificationRecordFactory extends Factory
{
    protected $model = AppNotificationRecord::class;

    public function definition(): array
    {
        $type = NotificationType::factory()->create();

        return [
            'app_id' => $type->app_id,
            'notification_type_id' => $type->id,
            'user_id' => User::factory(),
            'subject' => fake()->sentence(),
            'body' => fake()->paragraph(),
            'cta_label' => null,
            'cta_url' => null,
            'read_at' => null,
            'created_at' => now(),
        ];
    }
}
