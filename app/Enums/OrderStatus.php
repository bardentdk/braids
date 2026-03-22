<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending    = 'pending';
    case Processing = 'processing';
    case Shipped    = 'shipped';
    case Delivered  = 'delivered';
    case Cancelled  = 'cancelled';
    case Refunded   = 'refunded';

    public function label(): string
    {
        return match($this) {
            self::Pending    => 'En attente',
            self::Processing => 'En traitement',
            self::Shipped    => 'Expédié',
            self::Delivered  => 'Livré',
            self::Cancelled  => 'Annulé',
            self::Refunded   => 'Remboursé',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Pending    => 'warning',
            self::Processing => 'info',
            self::Shipped    => 'brand',
            self::Delivered  => 'success',
            self::Cancelled  => 'error',
            self::Refunded   => 'onyx',
        };
    }
}