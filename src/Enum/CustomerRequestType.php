<?php

namespace App\Enum;

enum CustomerRequestType: string
{
    case REPRESENTACION = 'representacion';

    public function label(): string
    {
        return match ($this) {
            self::REPRESENTACION => 'Representación',
        };
    }
}
