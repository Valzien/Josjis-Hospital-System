<?php

namespace App\Enums;

/**
 * Hari dalam seminggu untuk jadwal dokter.
 * Nilai integer mengikuti Carbon::dayOfWeek (0=Minggu ... 6=Sabtu).
 */
enum Day: int
{
    case Sunday = 0;
    case Monday = 1;
    case Tuesday = 2;
    case Wednesday = 3;
    case Thursday = 4;
    case Friday = 5;
    case Saturday = 6;

    public function label(): string
    {
        return match ($this) {
            self::Sunday => 'Minggu',
            self::Monday => 'Senin',
            self::Tuesday => 'Selasa',
            self::Wednesday => 'Rabu',
            self::Thursday => 'Kamis',
            self::Friday => 'Jumat',
            self::Saturday => 'Sabtu',
        };
    }

    public function short(): string
    {
        return match ($this) {
            self::Sunday => 'Min',
            self::Monday => 'Sen',
            self::Tuesday => 'Sel',
            self::Wednesday => 'Rab',
            self::Thursday => 'Kam',
            self::Friday => 'Jum',
            self::Saturday => 'Sab',
        };
    }

    public function isWorkingDay(): bool
    {
        return ! in_array($this, [self::Sunday, self::Saturday], true);
    }

    /** @return array<int, string> */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
