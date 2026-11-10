<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Booked = 'BOOKED';
    case CheckedIn = 'CHECKED_IN';
    case Completed = 'COMPLETED';
    case Cancelled = 'CANCELLED';
    case NoShow = 'NO_SHOW';

    public function label(): string
    {
        return match ($this) {
            self::Booked => 'Terjadwal',
            self::CheckedIn => 'Sudah Check-in',
            self::Completed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
            self::NoShow => 'Tidak Hadir',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Booked => 'primary',
            self::CheckedIn => 'info',
            self::Completed => 'success',
            self::Cancelled => 'danger',
            self::NoShow => 'secondary',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Booked => 'bi-calendar-check',
            self::CheckedIn => 'bi-person-check',
            self::Completed => 'bi-check-circle',
            self::Cancelled => 'bi-x-circle',
            self::NoShow => 'bi-person-dash',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Booked, self::CheckedIn], true);
    }

    /**
     * Status yang masih memakai slot / kuota sesi dokter.
     *
     * @return array<int, string>
     */
    public static function occupyingValues(): array
    {
        return [
            self::Booked->value,
            self::CheckedIn->value,
            self::Completed->value,
        ];
    }

    /** @return array<int, self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Booked => [self::CheckedIn, self::Cancelled, self::NoShow],
            self::CheckedIn => [self::Completed, self::Cancelled],
            self::Completed, self::Cancelled, self::NoShow => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
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
