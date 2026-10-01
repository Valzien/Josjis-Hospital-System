<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PatientController extends Controller
{
    public function index(Request $request): View
    {
        $patients = Patient::query()
            ->withCount(['medicalRecords' => fn ($q) => $q->where('doctor_id', auth()->user()->doctor?->id)])
            ->when($request->filled('q'), fn ($q) => $q->search($request->string('q')->trim()->value()))
            ->whereHas('medicalRecords', fn ($q) => $q->where('doctor_id', auth()->user()->doctor?->id))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('doctor.patients.index', [
            'patients' => $patients,
            'filters' => $request->only('q'),
        ]);
    }

    public function show(Patient $patient): View
    {
        $doctorId = auth()->user()->doctor?->id;

        return view('doctor.patients.show', [
            'patient' => $patient,
            'records' => $patient->medicalRecords()->with(['doctor', 'prescription.details.medicine'])->latest('examined_at')->paginate(10),
            'myRecords' => $patient->medicalRecords()->with('prescription')->where('doctor_id', $doctorId)->latest('examined_at')->get(),
            'queues' => $patient->queues()->with('doctor')->latest('queue_date')->limit(10)->get(),
        ]);
    }
}
