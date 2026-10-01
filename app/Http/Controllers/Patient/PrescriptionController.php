<?php

namespace App\Http\Controllers\Patient;

use App\Enums\PrescriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\Prescription;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrescriptionController extends Controller
{
    public function index(Request $request): View
    {
        $prescriptions = auth()->user()->patient
            ->prescriptions()
            ->with(['doctor', 'details'])
            ->withCount('details')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->value))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('to')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('patient.prescriptions.index', [
            'prescriptions' => $prescriptions,
            'statuses' => PrescriptionStatus::options(),
            'filters' => $request->only('status', 'from', 'to'),
        ]);
    }

    public function show(Prescription $prescription): View
    {
        abort_if($prescription->patient_id !== auth()->user()->patient?->id, 403);

        $prescription->load(['patient.user', 'doctor', 'medicalRecord', 'details.medicine', 'processedBy']);

        return view('patient.prescriptions.show', ['prescription' => $prescription]);
    }
}
