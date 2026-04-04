<?php

namespace App\Enums;

enum NotificationCategory: string
{
    case Transactional = 'transactional';
    case Operational = 'operational';
    case Informational = 'informational';
    case Promotional = 'promotional';
}
