<?php

namespace App\Enums;

enum Subject: string
{
    case SLOVENSKY_JAZYK = 'slovensky_jazyk';
    case MATEMATIKA = 'matematika';
    case INFORMATIKA = 'informatika';
    case PROGRAMOVANIE = 'programovanie';
    case FYZIKA = 'fyzika';
    case CHEMIA = 'chemia';
    case BIOLOGIA = 'biologia';
    case DEJEPIS = 'dejepis';
    case GEOGRAFIA = 'geografia';
    case GRAFIKA = 'grafika';
    case ANGLICKY_JAZYK = 'anglicky_jazyk';
    case ELEKTRONIKA = 'elektronika';
    case ROBOTIKA = 'robotika';
    case DATABAZY = 'databazy';
    case ALGORITMY = 'algoritmy';
    case EKONOMIKA = 'ekonomika';
    case FINANCIE = 'financie';

    public function label(): string
    {
        return match($this) {
            self::SLOVENSKY_JAZYK => 'Slovenský Jazyk',
            self::MATEMATIKA => 'Matematika',
            self::INFORMATIKA => 'Informatika',
            self::PROGRAMOVANIE => 'Programovanie',
            self::FYZIKA => 'Fyzika',
            self::CHEMIA => 'Chémia',
            self::BIOLOGIA => 'Biológia',
            self::DEJEPIS => 'Dejepis',
            self::GEOGRAFIA => 'Geografia',
            self::GRAFIKA => 'Grafika',
            self::ANGLICKY_JAZYK => 'Anglický Jazyk',
            self::ELEKTRONIKA => 'Elektronika',
            self::ROBOTIKA => 'Robotika',
            self::DATABAZY => 'Databázy',
            self::ALGORITMY => 'Algoritmy',
            self::EKONOMIKA => 'Ekonomika',
            self::FINANCIE => 'Financie',
        };
    }

    public static function options(): array
    {
        return array_map(fn($c) => [
            'value' => $c->value,
            'label' => $c->label(),
        ], self::cases());
    }
}
