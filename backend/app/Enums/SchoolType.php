<?php

namespace App\Enums;

enum SchoolType: string
{
    case ELEMENTARY = 'ZS';
    case HIGH = 'SS';
    case UNIVERSITY = 'VS';

    public function label(): string
    {
        return match($this) {
            self::ELEMENTARY => 'Základná Škola',
            self::HIGH => 'Stredná Škola',
            self::UNIVERSITY => 'Vysoká Škola',
        };
    }

    public function maxYear(): int
    {
        return match($this) {
            self::ELEMENTARY => 9,
            self::HIGH => 5,
            self::UNIVERSITY => 5,
        };
    }

    public static function options(): array
    {
        return array_map(fn($c) => [
            'value' => $c->value,
            'label' => $c->label(),
            'maxYear' => $c->maxYear(),
        ], self::cases());
    }
}
