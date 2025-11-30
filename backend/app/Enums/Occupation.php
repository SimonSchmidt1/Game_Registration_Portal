<?php

namespace App\Enums;

enum Occupation: string
{
    case PROGRAMATOR = 'Programátor';
    case GRAFIK_2D = 'Grafik 2D';
    case GRAFIK_3D = 'Grafik 3D';
    case TESTER = 'Tester';
    case ANIMATOR = 'Animátor';

    public function label(): string
    {
        return $this->value;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

