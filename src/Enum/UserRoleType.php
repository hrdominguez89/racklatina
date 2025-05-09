<?php

namespace App\Enum;

enum UserRoleType: string
{
    case INTERNAL = 'internal';
    case EXTERNAL = 'external';
}
