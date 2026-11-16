<?php

namespace App\Enums;

enum AdmissionStatus: string
{
    case Active = 'ACTIVE';
    case Discharged = 'DISCHARGED';
    case Cancelled = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Dirawat',
            self::Discharged => 'Sudah Pulang',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Active => 'primary',
            self::Discharged => 'success',
            self::Cancelled => 'danger',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Active => 'bi-hospital',
            self::Discharged => 'bi-box-arrow-right',
            self::Cancelled => 'bi-x-circle',
        };
    }

    public function isOpen(): bool
    {
        return $this === self::Active;
    }

    /** @return array<int, string> */
    public static function openValues(): array
    {
        return [self::Active->value];
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
