<?php

namespace App\Enums;

enum AdmissionChargeType: string
{
    case Room = 'ROOM';
    case Service = 'SERVICE';
    case Medicine = 'MEDICINE';
    case Other = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::Room => 'Kamar',
            self::Service => 'Tindakan',
            self::Medicine => 'Obat',
            self::Other => 'Lainnya',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Room => 'primary',
            self::Service => 'warning',
            self::Medicine => 'info',
            self::Other => 'secondary',
        };
    }

    public function invoiceItemType(): string
    {
        return match ($this) {
            self::Room => 'room',
            self::Service => 'service',
            self::Medicine => 'medicine',
            self::Other => 'other',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Room => 'bi-door-closed',
            self::Service => 'bi-tags',
            self::Medicine => 'bi-capsule',
            self::Other => 'bi-three-dots',
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
