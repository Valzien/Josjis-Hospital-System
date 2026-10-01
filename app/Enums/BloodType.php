<?php

namespace App\Enums;

enum BloodType: string
{
    case A = 'A';
    case B = 'B';
    case AB = 'AB';
    case O = 'O';
    case ANegative = 'A-';
    case BNegative = 'B-';
    case ABNegative = 'AB-';
    case ONegative = 'O-';

    public function label(): string
    {
        return $this->value;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return [
            self::A->value => 'A',
            self::B->value => 'B',
            self::AB->value => 'AB',
            self::O->value => 'O',
            self::ANegative->value => 'A-',
            self::BNegative->value => 'B-',
            self::ABNegative->value => 'AB-',
            self::ONegative->value => 'O-',
        ];
    }
}
