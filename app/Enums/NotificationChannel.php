<?php

namespace App\Enums;

enum NotificationChannel: string
{
    case Email = 'email';
    case Telegram = 'telegram';
    case Database = 'database';
}
