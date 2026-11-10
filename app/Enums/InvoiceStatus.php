<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Unpaid = 'UNPAID';
    case Partial = 'PARTIAL';
    case Paid = 'PAID';
    case Cancelled = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Belum Dibayar',
            self::Partial => 'Dibayar Sebagian',
            self::Paid => 'Lunas',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Unpaid => 'warning',
            self::Partial => 'info',
            self::Paid => 'success',
            self::Cancelled => 'danger',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Unpaid => 'bi-hourglass-split',
            self::Partial => 'bi-hourglass-bottom',
            self::Paid => 'bi-check-circle',
            self::Cancelled => 'bi-x-circle',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Unpaid, self::Partial], true);
    }

    /** @return array<int, string> */
    public static function openValues(): array
    {
        return [self::Unpaid->value, self::Partial->value];
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
