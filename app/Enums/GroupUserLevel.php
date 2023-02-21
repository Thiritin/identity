<?php

namespace App\Enums;

enum GroupUserLevel: string
{
    case Invited = 'invited';
    case Member = 'member';
    case Moderator = 'moderator';
    case Admin = 'admin';
    case Owner = 'owner';

}
