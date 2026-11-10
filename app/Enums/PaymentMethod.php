<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'CASH';
    case Transfer = 'TRANSFER';
    case Qris = 'QRIS';
    case Debit = 'DEBIT';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Tunai',
            self::Transfer => 'Transfer Bank',
            self::Qris => 'QRIS',
            self::Debit => 'Kartu Debit',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Cash => 'bi-cash-stack',
            self::Transfer => 'bi-bank',
            self::Qris => 'bi-qr-code',
            self::Debit => 'bi-credit-card',
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

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
