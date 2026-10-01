<?php

namespace App\Http\Controllers\Reception;

use App\Enums\Gender;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reception\PatientRegistrationRequest;
use App\Models\Patient;
use App\Services\AuditLogger;
use App\Services\NumberGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PatientController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly NumberGenerator $numbers,
    ) {}

    public function index(Request $request): View
    {
        $patients = Patient::query()
            ->withCount(['queues', 'medicalRecords'])
            ->when($request->filled('q'), fn ($q) => $q->search($request->string('q')->trim()->value()))
            ->when($request->filled('gender'), fn ($q) => $q->where('gender', $request->string('gender')->value))
            ->when($request->boolean('incomplete'), fn ($q) => $q->where(fn ($w) => $w->whereNull('gender')->orWhereNull('birth_date')->orWhereNull('phone')->orWhereNull('address')))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('reception.patients.index', [
            'patients' => $patients,
            'genders' => Gender::options(),
            'filters' => $request->only('q', 'gender', 'incomplete'),
        ]);
    }

    public function show(Patient $patient): View
    {
        $patient->load('user');

        return view('reception.patients.show', [
            'patient' => $patient,
            'queues' => $patient->queues()->with('doctor')->latest('queue_date')->limit(10)->get(),
            'records' => $patient->medicalRecords()->with('doctor')->latest('examined_at')->limit(5)->get(),
            'prescriptions' => $patient->prescriptions()->with('doctor')->latest()->limit(5)->get(),
            'openQueue' => $patient->queues()->open()->latest('id')->first(),
        ]);
    }

    public function create(): View
    {
        return view('reception.registration.form', [
            'patient' => new Patient(['medical_record_number' => $this->numbers->patientNumberPreview()]),
            'mode' => 'create',
        ]);
    }

    public function store(PatientRegistrationRequest $request): RedirectResponse
    {
        $data = $request->payload();

        // Pembuatan nomor harus satu transaksi dengan penyimpanan pasien agar
        // nomor tidak terpakai tanpa menghasilkan data.
        $patient = DB::transaction(function () use ($data) {
            $data['medical_record_number'] = $this->numbers->patientNumber();

            return Patient::create($data);
        }, 3);

        $this->audit->created('patient', 'Patient', $patient->id, "Mendaftarkan pasien {$patient->name} dengan nomor {$patient->medical_record_number}.");

        return redirect()
            ->route('reception.registration.create')
            ->with('success', "Pasien {$patient->name} berhasil didaftarkan (No. RM {$patient->medical_record_number}). Silakan buat antrean bila perlu.");
    }

    public function edit(Patient $patient): View
    {
        return view('reception.registration.form', ['patient' => $patient, 'mode' => 'edit']);
    }

    public function update(PatientRegistrationRequest $request, Patient $patient): RedirectResponse
    {
        $patient->update($request->payload());

        $this->audit->updated('patient', 'Patient', $patient->id, "Memperbarui data pasien {$patient->name} oleh resepsionis.");

        return redirect()->route('reception.patients.show', $patient)->with('success', "Data {$patient->name} berhasil diperbarui.");
    }

    public function history(Request $request): View
    {
        $patients = Patient::query()
            ->search($request->string('q')->trim()->value())
            ->whereHas('queues', fn ($q) => $q->onDate(now()))
            ->withCount(['queues' => fn ($q) => $q->onDate(now())])
            ->orderBy('name')
            ->limit(50)
            ->get();

        return view('reception.registration.history', ['patients' => $patients, 'q' => $request->input('q')]);
    }
}
