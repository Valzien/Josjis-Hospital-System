<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\MedicalRecord;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HistoryController extends Controller
{
    public function index(Request $request): View
    {
        $patient = auth()->user()->patient;

        $records = $patient?->medicalRecords()
            ->with(['doctor', 'prescription'])
            ->when($request->filled('q'), fn ($q) => $q->search($request->string('q')->trim()->value()))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('examined_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('examined_at', '<=', $request->date('to')))
            ->latest('examined_at')
            ->paginate(10)
            ->withQueryString() ?? collect();

        return view('patient.history.index', [
            'patient' => $patient,
            'records' => $records,
            'filters' => $request->only('q', 'from', 'to'),
        ]);
    }

    public function show(MedicalRecord $record): View
    {
        abort_if($record->patient_id !== auth()->user()->patient?->id, 403);

        $record->load(['patient.user', 'doctor', 'queue', 'prescription.details.medicine']);

        return view('patient.history.show', ['record' => $record]);
    }
}
