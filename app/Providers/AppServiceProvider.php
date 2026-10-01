<?php

namespace App\Providers;

use App\Enums\PrescriptionStatus;
use App\Enums\QueueStatus;
use App\Models\DoctorSchedule;
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
        View::composer('*', function ($view) {
            $view->with('jhsConfig', config('jhs'));
            $view->with('navBadges', $this->navigationBadges());
        });

        View::share('hospitalName', config('jhs.name'));
    }

    /**
     * Angka kecil pada sidebar menu.
     *
     * @return array<string, int>
     */
    private function navigationBadges(): array
    {
        $user = auth()->user();

        if ($user === null) {
            return [];
        }

        return Cache::remember('jhs.nav-badges.'.$user->id, 60, function () use ($user) {
            $today = now()->toDateString();

            $waiting = $user->isDoctor() || $user->isReceptionist()
                ? Queue::query()->onDate($today)->where('status', QueueStatus::Waiting->value)
                    ->when($user->isDoctor(), fn ($q) => $q->where('doctor_id', $user->doctor?->id))
                    ->count()
                : 0;

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
