<?php

namespace App\Enums;

enum RoomClass: string
{
    case Vip = 'VIP';
    case Class1 = 'KELAS_1';
    case Class2 = 'KELAS_2';
    case Class3 = 'KELAS_3';
    case Icu = 'ICU';

    public function label(): string
    {
        return match ($this) {
            self::Vip => 'VIP',
            self::Class1 => 'Kelas 1',
            self::Class2 => 'Kelas 2',
            self::Class3 => 'Kelas 3',
            self::Icu => 'ICU',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Vip => 'primary',
            self::Class1 => 'info',
            self::Class2 => 'success',
            self::Class3 => 'secondary',
            self::Icu => 'danger',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
