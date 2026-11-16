<?php

namespace App\Enums;

enum ActiveStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Inactive => 'Nonaktif',
        };
    }

    public function badge(): string
    {
        return $this === self::Active ? 'success' : 'secondary';
    }

    public function icon(): string
    {
        return $this === self::Active ? 'bi-check-circle' : 'bi-slash-circle';
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return [
            self::Active->value => self::Active->label(),
            self::Inactive->value => self::Inactive->label(),
        ];
    }
}
