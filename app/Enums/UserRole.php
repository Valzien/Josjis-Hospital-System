<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Receptionist = 'receptionist';
    case Doctor = 'doctor';
    case Pharmacist = 'pharmacist';
    case Cashier = 'cashier';
    case Patient = 'patient';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Receptionist => 'Resepsionis',
            self::Doctor => 'Dokter',
            self::Pharmacist => 'Apoteker',
            self::Cashier => 'Kasir',
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
            self::Cashier => 'Kasir',
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
            self::Cashier => 'cashier',
            self::Patient => 'patient',
        };
    }

    public function homeRoute(): string
    {
        return $this->routePrefix().'.dashboard';
    }

    /**
     * Nama route halaman login milik role.
     *
     * Staf punya portal login terpisah, sedangkan pasien tetap memakai /login.
     */
    public function loginRoute(): string
    {
        return match ($this) {
            self::Admin => 'admin.login',
            self::Receptionist => 'reception.login',
            self::Doctor => 'doctor.login',
            self::Pharmacist => 'pharmacy.login',
            self::Cashier => 'cashier.login',
            self::Patient => 'login',
        };
    }

    /** Prefix URL portal login staf (null untuk pasien). */
    public function portalPrefix(): ?string
    {
        return $this === self::Patient ? null : $this->routePrefix();
    }

    public function icon(): string
    {
        return match ($this) {
            self::Admin => 'bi-shield-lock',
            self::Receptionist => 'bi-headset',
            self::Doctor => 'bi-heart-pulse',
            self::Pharmacist => 'bi-capsule',
            self::Cashier => 'bi-cash-coin',
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
            self::Cashier => 'danger',
            self::Patient => 'secondary',
        };
    }

    /** Role yang memiliki portal login staf terpisah. */
    public static function staff(): array
    {
        return array_values(array_filter(self::cases(), fn (self $role) => ! $role->isPatient()));
    }

    public function isPatient(): bool
    {
        return $this === self::Patient;
    }

    public static function fromPortal(string $portal): ?self
    {
        return match ($portal) {
            'admin' => self::Admin,
            'reception' => self::Receptionist,
            'doctor' => self::Doctor,
            'pharmacy' => self::Pharmacist,
            'cashier' => self::Cashier,
            'patient' => self::Patient,
            default => null,
        };
    }

    /** Portal login yang cocok dengan nama route (mis. "admin.dashboard" -> admin.login). */
    public static function loginRouteForRouteName(?string $routeName): ?string
    {
        if (blank($routeName)) {
            return null;
        }

        $prefix = str_contains($routeName, '.') ? str($routeName)->before('.')->value() : null;

        $role = $prefix ? self::fromPortal($prefix) : null;

        return $role?->loginRoute();
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
