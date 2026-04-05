<?php

namespace Database\Seeders;

use App\Enums\NotificationCategory;
use App\Models\App;
use App\Models\NotificationType;
use Illuminate\Database\Seeder;

class NotificationTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'portal' => [
                [
                    'key' => 'security.login_alert',
                    'name' => 'New sign-in alert',
                    'description' => 'Sent when a new device or location signs in to your account.',
                    'category' => NotificationCategory::Transactional,
                    'default_channels' => ['email', 'database'],
                ],
            ],
        ];

        foreach ($types as $systemName => $appTypes) {
            $app = App::where('system_name', $systemName)->first();
            if (! $app) {
                continue;
            }

            if (! $app->allow_notifications) {
                $app->forceFill(['allow_notifications' => true])->save();
            }

            foreach ($appTypes as $attributes) {
                NotificationType::updateOrCreate(
                    ['app_id' => $app->id, 'key' => $attributes['key']],
                    $attributes + ['disabled' => false],
                );
            }
        }
    }
}
