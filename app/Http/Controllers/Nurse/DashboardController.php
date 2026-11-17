<?php

namespace App\Http\Controllers\Nurse;

use App\Enums\AdmissionStatus;
use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\AdmissionVisit;
use App\Models\Bed;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $today = now()->toDateString();

        return view('nurse.dashboard', [
            'activeAdmissions' => Admission::query()
                ->with(['patient', 'doctor', 'room', 'bed', 'latestVisit'])
                ->active()
                ->orderBy('admitted_at')
                ->limit(10)
                ->get(),
            'recentVisits' => AdmissionVisit::query()
                ->with(['admission.patient', 'admission.doctor', 'createdBy'])
                ->whereDate('created_at', $today)
                ->latest('visited_at')
                ->limit(10)
                ->get(),
            'summary' => [
                'active' => Admission::query()->active()->count(),
                'admitted_today' => Admission::query()->whereDate('admitted_at', $today)->count(),
                'visits_today' => AdmissionVisit::query()->whereDate('visited_at', $today)->count(),
                'available_beds' => Bed::query()->available()->count(),
                'discharged_today' => Admission::query()
                    ->where('status', AdmissionStatus::Discharged->value)
                    ->whereDate('discharged_at', $today)
                    ->count(),
            ],
        ]);
    }
}
