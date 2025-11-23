<?php

namespace App\Enums;

enum TeamRole: string
{
    case SCRUM_MASTER = 'scrum_master';
    case MEMBER = 'member';

    public function label(): string
    {
        return match($this) {
            self::SCRUM_MASTER => 'Scrum Master',
            self::MEMBER => 'Člen',
        };
    }
}
