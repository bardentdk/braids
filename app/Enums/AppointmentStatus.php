<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Pending   = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    case Completed = 'completed';
    case NoShow    = 'no_show';

    public function label(): string
    {
        return match($this) {
            self::Pending   => 'En attente',
            self::Confirmed => 'Confirmé',
            self::Cancelled => 'Annulé',
            self::Completed => 'Terminé',
            self::NoShow    => 'Absent',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Pending   => 'warning',
            self::Confirmed => 'info',
            self::Cancelled => 'error',
            self::Completed => 'success',
            self::NoShow    => 'error',
        };
    }
}