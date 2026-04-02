<?php

namespace App\Enums;

enum TwoFactorTypeEnum: string
{
    case TOTP = 'totp';
    case YUBIKEY = 'yubikey';
    case BackupCodes = 'backup_codes';
    case PASSKEY = 'passkey';
    case SECURITY_KEY = 'security_key';
}
