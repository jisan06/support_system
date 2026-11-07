<?php

namespace App\Enums;

enum TicketMessageType: string
{
    case REPLY = 'reply';
    case INTERNAL_NOTE = 'internal_note';

    public function label(): string
    {
        return match ($this) {
            self::REPLY => 'Public Reply',
            self::INTERNAL_NOTE => 'Internal Note',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

