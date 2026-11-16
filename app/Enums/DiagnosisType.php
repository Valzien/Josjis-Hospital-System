<?php

namespace App\Enums;

enum DiagnosisType: string
{
    case Primary = 'PRIMARY';
    case Secondary = 'SECONDARY';

    public function label(): string
    {
        return match ($this) {
            self::Primary => 'Diagnosis Utama',
            self::Secondary => 'Diagnosis Tambahan',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Primary => 'primary',
            self::Secondary => 'secondary',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Primary => 'bi-star-fill',
            self::Secondary => 'bi-bookmark',
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
