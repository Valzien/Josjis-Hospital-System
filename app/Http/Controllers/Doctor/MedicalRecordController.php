<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\MedicalRecord;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MedicalRecordController extends Controller
{
    public function index(Request $request): View
    {
        $records = MedicalRecord::query()
            ->with(['patient', 'doctor', 'prescription'])
            ->when($request->filled('q'), fn ($q) => $q->search($request->string('q')->trim()->value()))
            ->when($request->filled('mine'), fn ($q) => $q->where('doctor_id', auth()->user()->doctor?->id))
            ->when($request->filled('doctor_id'), fn ($q) => $q->where('doctor_id', $request->integer('doctor_id')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('examined_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('examined_at', '<=', $request->date('to')))
            ->latest('examined_at')
            ->paginate(20)
            ->withQueryString();

        return view('doctor.medical-records.index', [
            'records' => $records,
            'filters' => $request->only('q', 'mine', 'doctor_id', 'from', 'to'),
            'doctors' => Doctor::query()->orderBy('name')->get(),
        ]);
    }

    public function show(MedicalRecord $record): View
    {
        $record->load(['patient.user', 'doctor', 'queue', 'prescription.details.medicine']);

        return view('doctor.medical-records.show', ['record' => $record]);
    }
}
