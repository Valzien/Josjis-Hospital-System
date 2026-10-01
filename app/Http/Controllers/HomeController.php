<?php

namespace App\Http\Controllers;

use App\Enums\QueueStatus;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Patient;
use App\Models\Queue;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function landing(): View
    {
        return view('landing', [
            'stats' => [
                ['label' => 'Dokter Aktif', 'value' => Doctor::query()->active()->count()],
                ['label' => 'Spesialisasi', 'value' => Doctor::query()->active()->distinct()->count('specialization')],
                ['label' => 'Pasien Terdaftar', 'value' => Patient::query()->count()],
            ],
            'doctors' => Doctor::query()
                ->active()
                ->orderBy('name')
                ->limit(6)
                ->get(),
            'todaySchedules' => DoctorSchedule::query()
                ->with('doctor')
                ->active()
                ->forDay(now())
                ->orderBy('start_time')
                ->limit(6)
                ->get(),
            'queuesToday' => Queue::query()->onDate(now())->count(),
            'waitingToday' => Queue::query()->onDate(now())->where('status', QueueStatus::Waiting->value)->count(),
        ]);
    }

    public function dispatch(): RedirectResponse
    {
        return redirect()->route(request()->user()->dashboardRoute());
    }
}
