<?php

namespace App\Enums;

enum ServiceCategory: string
{
    case Braids     = 'braids';
    case Twists     = 'twists';
    case Locs       = 'locs';
    case Natural    = 'natural';
    case Extensions = 'extensions';
    case Kids       = 'kids';
    case Other      = 'other';

    public function label(): string
    {
        return match($this) {
            self::Braids     => 'Box Braids',
            self::Twists     => 'Twists',
            self::Locs       => 'Locs / Dreadlocks',
            self::Natural    => 'Naturel',
            self::Extensions => 'Extensions',
            self::Kids       => 'Enfants',
            self::Other      => 'Autre',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::Braids     => 'PhScissors',
            self::Twists     => 'PhSpiral',
            self::Locs       => 'PhAnchor',
            self::Natural    => 'PhLeaf',
            self::Extensions => 'PhMagicWand',
            self::Kids       => 'PhStar',
            self::Other      => 'PhDotsThree',
        };
    }
}