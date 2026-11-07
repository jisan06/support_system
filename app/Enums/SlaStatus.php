<?php

namespace App\Enums;

enum SlaStatus: string
{
    case ON_TRACK = 'on_track';
    case APPROACHING_BREACH = 'approaching_breach';
    case BREACHED = 'breached';
    case COMPLETED = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::ON_TRACK => 'On Track',
            self::APPROACHING_BREACH => 'Approaching Breach',
            self::BREACHED => 'Breached',
            self::COMPLETED => 'Completed',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::ON_TRACK => 'bg-success',
            self::APPROACHING_BREACH => 'bg-warning text-dark',
            self::BREACHED => 'bg-danger',
            self::COMPLETED => 'bg-info',
        };
    }
}

