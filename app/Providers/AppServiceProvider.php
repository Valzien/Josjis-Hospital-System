<?php

namespace App\Providers;

use App\Enums\AppointmentStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PrescriptionStatus;
use App\Enums\QueueStatus;
use App\Models\Appointment;
use App\Models\DoctorSchedule;
use App\Models\Invoice;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Queue;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    private static ?array $cachedBadges = null;

    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Schema::defaultStringLength(191);

        $this->shareViewData();
    }

    private function shareViewData(): void
    {
        View::share('jhsConfig', config('jhs'));
        View::share('hospitalName', config('jhs.name'));

        View::composer(['partials.sidebar', 'partials.topbar', 'layouts.app'], function ($view) {
            $view->with('navBadges', $this->navigationBadges());
        });
    }

    /**
     * Angka kecil pada sidebar menu.
     *
     * @return array<string, int>
     */
    private function navigationBadges(): array
    {
        if (static::$cachedBadges !== null) {
            return static::$cachedBadges;
        }

        $user = auth()->user();

        if ($user === null) {
            return static::$cachedBadges = [];
        }

        return static::$cachedBadges = Cache::remember('jhs.nav-badges.'.$user->id, 60, function () use ($user) {
            $today = now()->toDateString();

            $waiting = $user->isDoctor()
                ? Queue::query()->onDate($today)->where('status', QueueStatus::Called->value)
                    ->where('doctor_id', $user->doctor?->id)
                    ->count()
                : ($user->isReceptionist()
                    ? Queue::query()->onDate($today)->where('status', QueueStatus::Waiting->value)->count()
                    : 0);

            return [
                'waiting' => (int) $waiting,
                'prescription' => $user->isPharmacist()
                    ? Prescription::query()->whereIn('status', [
                        PrescriptionStatus::Pending->value,
                        PrescriptionStatus::Processing->value,
                        PrescriptionStatus::Ready->value,
                    ])->count()
                    : 0,
                'low_stock' => $user->isPharmacist()
                    ? Medicine::query()->active()->lowStock()->count()
                    : 0,
                'unpaid' => $user->isCashier() || $user->isAdmin()
                    ? Invoice::query()->whereIn('status', InvoiceStatus::openValues())->count()
                    : 0,
                'appointments' => $user->isReceptionist()
                    ? Appointment::query()->onDate($today)->status(AppointmentStatus::Booked)->count()
                    : 0,
            ];
        });
    }

    /**
     * Helper kecil yang sering dipakai di view.
     */
    public static function activeScheduleCount(?int $doctorId = null): int
    {
        return DoctorSchedule::query()
            ->active()
            ->forDay(now())
            ->when($doctorId, fn ($q) => $q->where('doctor_id', $doctorId))
            ->count();
    }

    public static function incompleteProfileCount(): int
    {
        return Patient::query()->where(function ($q) {
            $q->whereNull('gender')->orWhereNull('birth_date')->orWhereNull('phone')->orWhereNull('address');
        })->count();
    }

    public static function isDemoMode(): bool
    {
        return (bool) Setting::bool('demo_mode', false);
    }
}
