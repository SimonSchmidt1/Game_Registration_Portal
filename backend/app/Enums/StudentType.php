<?php

namespace App\Enums;

enum StudentType: string
{
    case DAILY = 'daily';
    case EXTERNAL = 'external';

    public function label(): string
    {
        return match($this) {
            self::DAILY => 'Denný študent',
            self::EXTERNAL => 'Externý študent',
        };
    }
}
