<?php

namespace App\Enums;

enum TransactionType: string
{
    case In = 'IN';
    case Out = 'OUT';
    case Adjustment = 'ADJUSTMENT';

    public function label(): string
    {
        return match ($this) {
            self::In => 'Masuk',
            self::Out => 'Keluar',
            self::Adjustment => 'Penyesuaian',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::In => 'success',
            self::Out => 'danger',
            self::Adjustment => 'warning',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::In => 'bi-arrow-down-circle',
            self::Out => 'bi-arrow-up-circle',
            self::Adjustment => 'bi-sliders',
        };
    }

    /** Tanda pengaruhnya ke stok. */
    public function sign(): int
    {
        return match ($this) {
            self::In => 1,
            self::Out => -1,
            self::Adjustment => 0,
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
