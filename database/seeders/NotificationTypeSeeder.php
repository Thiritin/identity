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
                    'key' => 'registration.confirmed',
                    'name' => 'Registration confirmed',
                    'description' => 'Sent when your convention registration is confirmed.',
                    'category' => NotificationCategory::Transactional,
                    'default_channels' => ['email', 'database'],
                ],
                [
                    'key' => 'registration.payment_due',
                    'name' => 'Payment reminder',
                    'description' => 'Sent when a payment for your registration is due.',
                    'category' => NotificationCategory::Transactional,
                    'default_channels' => ['email'],
                ],
                [
                    'key' => 'announcements.general',
                    'name' => 'General announcements',
                    'description' => 'News and announcements from the Eurofurence team.',
                    'category' => NotificationCategory::Informational,
                    'default_channels' => ['email', 'database'],
                ],
                [
                    'key' => 'announcements.promotions',
                    'name' => 'Promotions & offers',
                    'description' => 'Occasional promotions, merchandise drops and special offers.',
                    'category' => NotificationCategory::Promotional,
                    'default_channels' => ['email'],
                ],
            ],
            'staff' => [
                [
                    'key' => 'shifts.assigned',
                    'name' => 'Shift assigned',
                    'description' => 'Sent when a new shift has been assigned to you.',
                    'category' => NotificationCategory::Operational,
                    'default_channels' => ['email', 'telegram', 'database'],
                ],
                [
                    'key' => 'shifts.reminder',
                    'name' => 'Shift reminder',
                    'description' => 'Reminder shortly before one of your shifts starts.',
                    'category' => NotificationCategory::Operational,
                    'default_channels' => ['telegram', 'database'],
                ],
            ],
            'admin' => [
                [
                    'key' => 'security.login_alert',
                    'name' => 'New sign-in alert',
                    'description' => 'Sent when a new device signs in to your admin account.',
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
