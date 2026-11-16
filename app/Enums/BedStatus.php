<?php

namespace App\Enums;

enum BedStatus: string
{
    case Available = 'AVAILABLE';
    case Occupied = 'OCCUPIED';
    case Maintenance = 'MAINTENANCE';
    case Inactive = 'INACTIVE';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Tersedia',
            self::Occupied => 'Terisi',
            self::Maintenance => 'Perbaikan',
            self::Inactive => 'Nonaktif',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Available => 'success',
            self::Occupied => 'danger',
            self::Maintenance => 'warning',
            self::Inactive => 'secondary',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Available => 'bi-check-circle',
            self::Occupied => 'bi-person-fill',
            self::Maintenance => 'bi-tools',
            self::Inactive => 'bi-slash-circle',
        };
    }

    public function isAvailable(): bool
    {
        return $this === self::Available;
    }

    public function isAssignable(): bool
    {
        return $this === self::Available;
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

    /** @return array<string, string> */
    public static function manualOptions(): array
    {
        return [
            self::Available->value => self::Available->label(),
            self::Maintenance->value => self::Maintenance->label(),
            self::Inactive->value => self::Inactive->label(),
        ];
    }
}
