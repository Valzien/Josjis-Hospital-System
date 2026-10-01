<?php

namespace App\Http\Controllers\Doctor;

use App\Enums\PrescriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\Prescription;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrescriptionController extends Controller
{
    public function index(Request $request): View
    {
        $prescriptions = Prescription::query()
            ->with(['patient', 'doctor', 'details'])
            ->withCount('details')
            ->where('doctor_id', auth()->user()->doctor?->id)
            ->when($request->filled('q'), fn ($q) => $q->search($request->string('q')->trim()->value()))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->value))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('doctor.prescriptions.index', [
            'prescriptions' => $prescriptions,
            'statuses' => PrescriptionStatus::options(),
            'filters' => $request->only('q', 'status'),
            'summary' => Prescription::query()
                ->where('doctor_id', auth()->user()->doctor?->id)
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
        ]);
    }

    public function show(Prescription $prescription): View
    {
        abort_if($prescription->doctor_id !== auth()->user()->doctor?->id, 403);

        $prescription->load(['patient.user', 'doctor', 'medicalRecord', 'details.medicine', 'processedBy']);

        return view('doctor.prescriptions.show', ['prescription' => $prescription]);
    }

    public function print(Prescription $prescription): View
    {
        abort_if($prescription->doctor_id !== auth()->user()->doctor?->id, 403);

        $prescription->load(['patient', 'doctor', 'medicalRecord', 'details.medicine', 'processedBy']);

        return view('doctor.prescriptions.print', ['prescription' => $prescription]);
    }
}
