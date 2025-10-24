<?php

namespace App\Enum;

enum CustomerRequestStatus: string
{
    case PENDIENTE = 'pendiente';
    case APROBADO = 'aprobado';
    case PARCIALMENTE_APROBADO = 'parcialmente_aprobado';
    case RECHAZADO = 'rechazado';

    public function label(): string
    {
        return match($this) {
            self::PENDIENTE => 'Pendiente',
            self::APROBADO => 'Aprobado',
            self::PARCIALMENTE_APROBADO => 'Parcialmente aprobado',
            self::RECHAZADO => 'Rechazado',
        };
    }
}
