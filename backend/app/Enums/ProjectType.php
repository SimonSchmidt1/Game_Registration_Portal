<?php

namespace App\Enums;

enum ProjectType: string
{
    case GAME = 'game';
    case APPLICATION = 'application';
    case LIBRARY = 'library';
    case OTHER = 'other';

    public function label(): string
    {
        return match($this) {
            self::GAME => 'Hra',
            self::APPLICATION => 'Aplikácia',
            self::LIBRARY => 'Knižnica',
            self::OTHER => 'Iné',
        };
    }
}
