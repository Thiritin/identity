<?php

namespace App\Enums;

enum GroupTypeEnum: string
{
    case Default = 'none';
    case Department = 'department';
    case Team = 'team';
    case Automated = 'automated';

}
