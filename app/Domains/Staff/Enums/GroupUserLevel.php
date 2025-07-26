<?php

namespace App\Domains\Staff\Enums;

enum GroupUserLevel: string
{
    case Member = 'member';
    case Moderator = 'moderator';
    case Admin = 'admin';
    case Owner = 'owner';

}
