<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Receptionist = 'receptionist';
    case Doctor = 'doctor';
    case Pharmacist = 'pharmacist';
    case Patient = 'patient';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Receptionist => 'Resepsionis',
            self::Doctor => 'Dokter',
            self::Pharmacist => 'Apoteker',
            self::Patient => 'Pasien',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Receptionist => 'Resepsionis',
            self::Doctor => 'Dokter',
            self::Pharmacist => 'Apoteker',
            self::Patient => 'Pasien',
        };
    }

    /** Prefix route prefix milik role. */
    public function routePrefix(): string
    {
        return match ($this) {
            self::Admin => 'admin',
            self::Receptionist => 'reception',
            self::Doctor => 'doctor',
            self::Pharmacist => 'pharmacy',
            self::Patient => 'patient',
        };
    }

    public function homeRoute(): string
    {
        return $this->routePrefix().'.dashboard';
    }

    public function icon(): string
    {
        return match ($this) {
            self::Admin => 'bi-shield-lock',
            self::Receptionist => 'bi-headset',
            self::Doctor => 'bi-heart-pulse',
            self::Pharmacist => 'bi-capsule',
            self::Patient => 'bi-person',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Admin => 'primary',
            self::Receptionist => 'info',
            self::Doctor => 'success',
            self::Pharmacist => 'warning',
            self::Patient => 'secondary',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** @return array<string, string> value => label */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
