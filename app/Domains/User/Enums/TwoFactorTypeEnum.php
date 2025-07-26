<?php

namespace App\Domains\User\Enums;

enum TwoFactorTypeEnum: string
{
    case TOTP = 'totp';
    case YUBIKEY = 'yubikey';
}
