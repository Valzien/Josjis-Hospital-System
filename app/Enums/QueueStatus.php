<?php

namespace App\Enums;

enum QueueStatus: string
{
    case Waiting = 'WAITING';
    case Called = 'CALLED';
    case InExamination = 'IN_EXAMINATION';
    case Completed = 'COMPLETED';
    case Cancelled = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::Waiting => 'Menunggu',
            self::Called => 'Dipanggil',
            self::InExamination => 'Dalam Pemeriksaan',
            self::Completed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
        };
    }

    /** Badge bootstrap */
    public function badge(): string
    {
        return match ($this) {
            self::Waiting => 'warning',
            self::Called => 'info',
            self::InExamination => 'primary',
            self::Completed => 'success',
            self::Cancelled => 'danger',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Waiting => 'bi-hourglass-split',
            self::Called => 'bi-megaphone',
            self::InExamination => 'bi-stethoscope',
            self::Completed => 'bi-check-circle',
            self::Cancelled => 'bi-x-circle',
        };
    }

    /** Status yang masih actively memakai slot antrean. */
    public function isOpen(): bool
    {
        return in_array($this, [self::Waiting, self::Called, self::InExamination], true);
    }

    public function isFinished(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled], true);
    }

    /**
     * Transisi status yang diizinkan. Menjaga integritas alur antrean.
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Waiting => [self::Called, self::InExamination, self::Cancelled],
            self::Called => [self::InExamination, self::Waiting, self::Cancelled],
            self::InExamination => [self::Completed, self::Cancelled],
            self::Completed, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    /** @return array<int, self> */
    public static function cancelledValues(): array
    {
        return [self::Cancelled->value];
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
