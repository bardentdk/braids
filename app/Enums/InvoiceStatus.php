<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft    = 'draft';
    case Sent     = 'sent';
    case Paid     = 'paid';
    case Overdue  = 'overdue';
    case Cancelled= 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::Draft    => 'Brouillon',
            self::Sent     => 'Envoyée',
            self::Paid     => 'Payée',
            self::Overdue  => 'En retard',
            self::Cancelled=> 'Annulée',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Draft    => 'onyx',
            self::Sent     => 'info',
            self::Paid     => 'success',
            self::Overdue  => 'error',
            self::Cancelled=> 'error',
        };
    }
}