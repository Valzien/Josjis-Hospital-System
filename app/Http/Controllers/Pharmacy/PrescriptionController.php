<?php

namespace App\Http\Controllers\Pharmacy;

use App\Enums\PrescriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Prescription;
use App\Services\AuditLogger;
use App\Services\PrescriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class PrescriptionController extends Controller
{
    public function __construct(
        private readonly PrescriptionService $prescriptions,
        private readonly AuditLogger $audit,
    ) {}

    public function index(Request $request): View
    {
        $prescriptions = Prescription::query()
            ->with(['patient', 'doctor'])
            ->withCount('details')
            ->when($request->filled('q'), fn ($q) => $q->search($request->string('q')->trim()->value()))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->value))
            ->when($request->filled('doctor_id'), fn ($q) => $q->where('doctor_id', $request->integer('doctor_id')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('to')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('pharmacy.prescriptions.index', [
            'prescriptions' => $prescriptions,
            'statuses' => PrescriptionStatus::options(),
            'doctors' => Doctor::query()->orderBy('name')->get(),
            'filters' => $request->only('q', 'status', 'doctor_id', 'from', 'to'),
            'summary' => $this->prescriptions->statusSummary(),
        ]);
    }

    public function show(Prescription $prescription): View
    {
        $prescription->load(['patient.user', 'doctor', 'medicalRecord', 'details.medicine', 'processedBy']);

        return view('pharmacy.prescriptions.show', [
            'prescription' => $prescription,
            'shortages' => $prescription->insufficientMedicines(),
        ]);
    }

    public function process(Prescription $prescription): RedirectResponse
    {
        try {
            $this->prescriptions->markProcessing($prescription);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->audit->updated('pharmacy', 'Prescription', $prescription->id, "Memulai proses resep {$prescription->code}.");

        return back()->with('success', "Resep {$prescription->code} sedang diproses.");
    }

    public function ready(Prescription $prescription): RedirectResponse
    {
        try {
            $this->prescriptions->markReady($prescription, auth()->id());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->audit->updated('pharmacy', 'Prescription', $prescription->id, "Resep {$prescription->code} siap diambil pasien.");

        return back()->with('success', "Resep {$prescription->code} ditandai siap diambil.");
    }

    public function complete(Prescription $prescription): RedirectResponse
    {
        $patient = $prescription->patient?->name;

        try {
            $this->prescriptions->dispense($prescription, auth()->id());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->audit->updated('pharmacy', 'Prescription', $prescription->id, "Menyerahkan obat resep {$prescription->code} kepada {$patient}. Stok obat diperbarui.");

        return back()->with('success', "Resep {$prescription->code} selesai. Obat telah diserahkan dan stok diperbarui.");
    }

    public function cancel(Request $request, Prescription $prescription): RedirectResponse
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:300']]);

        try {
            $this->prescriptions->cancel($prescription, $data['reason'] ?? null, auth()->id());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->audit->updated('pharmacy', 'Prescription', $prescription->id, "Membatalkan resep {$prescription->code}.");

        return back()->with('success', "Resep {$prescription->code} dibatalkan.");
    }

    public function print(Prescription $prescription): View
    {
        $prescription->load(['patient', 'doctor', 'medicalRecord', 'details.medicine', 'processedBy']);

        return view('pharmacy.prescriptions.print', ['prescription' => $prescription]);
    }
}
