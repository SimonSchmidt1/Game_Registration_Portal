<?php

namespace App\Enums;

enum StudentType: string
{
    case DAILY = 'denny';
    case EXTERNAL = 'externy';

    public function label(): string
    {
        return match($this) {
            self::DAILY => 'Denný študent',
            self::EXTERNAL => 'Externý študent',
        };
    }

    /**
     * Get the invite code prefix for this student type
     */
    public function codePrefix(): string
    {
        return match($this) {
            self::DAILY => 'DEN',
            self::EXTERNAL => 'EXT',
        };
    }

    /**
     * Get StudentType from invite code prefix
     */
    public static function fromCodePrefix(string $code): ?self
    {
        $prefix = strtoupper(substr($code, 0, 3));
        return match($prefix) {
            'DEN' => self::DAILY,
            'EXT' => self::EXTERNAL,
            default => null,
        };
    }
}
