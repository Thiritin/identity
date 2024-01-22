<?php

namespace App\Enums;

enum TwoFactorTypeEnum: string
{
    case TOTP = 'totp';
    case YUBIKEY = 'yubikey';
}
