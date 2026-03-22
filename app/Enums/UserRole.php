<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin  = 'admin';
    case Client = 'client';

    public function label(): string
    {
        return match($this) {
            self::Admin  => 'Administratrice',
            self::Client => 'Client',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Admin  => 'onyx',
            self::Client => 'cognac',
        };
    }
}