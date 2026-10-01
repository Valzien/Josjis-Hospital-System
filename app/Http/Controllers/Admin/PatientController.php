<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Gender;
use App\Enums\QueueStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PatientRequest;
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
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.patients.index', [
            'patients' => $patients,
            'genders' => Gender::options(),
            'filters' => $request->only('q', 'gender'),
        ]);
    }

    public function create(): View
    {
        return view('admin.patients.form', [
            'patient' => new Patient(['medical_record_number' => $this->numbers->patientNumberPreview()]),
        ]);
    }

    public function store(PatientRequest $request): RedirectResponse
    {
        $data = $request->payload();

        // Pembuatan nomor harus satu transaksi dengan penyimpanan pasien agar
        // nomor tidak terpakai tanpa menghasilkan data.
        $patient = DB::transaction(function () use ($data) {
            $data['medical_record_number'] = $this->numbers->patientNumber();

            return Patient::create($data);
        }, 3);

        $this->audit->created('patient', 'Patient', $patient->id, "Mendaftarkan pasien baru: {$patient->name} ({$patient->medical_record_number}).");

        return redirect()->route('admin.patients.index')->with('success', "Pasien {$patient->name} berhasil didaftarkan dengan nomor {$patient->medical_record_number}.");
    }

    public function show(Patient $patient): View
    {
        $patient->load('user');

        return view('admin.patients.show', [
            'patient' => $patient,
            'queues' => $patient->queues()->with('doctor')->latest('queue_date')->limit(10)->get(),
            'records' => $patient->medicalRecords()->with('doctor')->latest('examined_at')->limit(10)->get(),
            'prescriptions' => $patient->prescriptions()->with('doctor')->latest()->limit(10)->get(),
            'stats' => [
                'queues' => $patient->queues()->count(),
                'records' => $patient->medicalRecords()->count(),
                'prescriptions' => $patient->prescriptions()->count(),
                'completed' => $patient->queues()->where('status', QueueStatus::Completed->value)->count(),
            ],
        ]);
    }

    public function edit(Patient $patient): View
    {
        return view('admin.patients.form', ['patient' => $patient]);
    }

    public function update(PatientRequest $request, Patient $patient): RedirectResponse
    {
        $patient->update($request->payload());

        $this->audit->updated('patient', 'Patient', $patient->id, "Memperbarui data pasien {$patient->name}.");

        return redirect()->route('admin.patients.index')->with('success', "Data pasien {$patient->name} berhasil diperbarui.");
    }

    public function destroy(Patient $patient): RedirectResponse
    {
        $name = $patient->name;
        $patient->delete();

        $this->audit->deleted('patient', 'Patient', $patient->id, "Menghapus data pasien {$name}.");

        return redirect()->route('admin.patients.index')->with('success', "Data pasien {$name} telah dihapus.");
    }
}
