<?php

namespace App\Enums;

enum AccountType: string
{
    case ADMIN = 'admin';
    case CUSTOMER = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Admin',
            self::CUSTOMER => 'Customer',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

