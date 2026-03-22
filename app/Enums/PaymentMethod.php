<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash     = 'cash';
    case Card     = 'card';
    case Transfer = 'transfer';
    case Paypal   = 'paypal';
    case Lydia    = 'lydia';
    case Sumeria  = 'sumeria';
    case Other    = 'other';

    public function label(): string
    {
        return match($this) {
            self::Cash     => 'Espèces',
            self::Card     => 'Carte bancaire',
            self::Transfer => 'Virement bancaire',
            self::Paypal   => 'PayPal',
            self::Lydia    => 'Lydia',
            self::Sumeria  => 'Sumeria',
            self::Other    => 'Autre',
        };
    }
}