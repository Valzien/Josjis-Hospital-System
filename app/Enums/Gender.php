<?php

namespace App\Enums;

enum Gender: string
{
    case Male = 'L';
    case Female = 'P';

    public function label(): string
    {
        return match ($this) {
            self::Male => 'Laki-laki',
            self::Female => 'Perempuan',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return [
            self::Male->value => self::Male->label(),
            self::Female->value => self::Female->label(),
        ];
    }
}
