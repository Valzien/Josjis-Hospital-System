<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ActiveStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DoctorRequest;
use App\Models\Doctor;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DoctorController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request): View
    {
        $doctors = Doctor::query()
            ->withCount(['schedules', 'queues', 'medicalRecords'])
            ->when($request->filled('q'), fn ($q) => $q->search($request->string('q')->trim()->value()))
            ->when($request->filled('specialization'), fn ($q) => $q->where('specialization', $request->string('specialization')->value()))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->value()))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('admin.doctors.index', [
            'doctors' => $doctors,
            'specializations' => Doctor::query()->distinct()->orderBy('specialization')->pluck('specialization'),
            'statuses' => ActiveStatus::options(),
            'filters' => $request->only('q', 'specialization', 'status'),
        ]);
    }

    public function create(): View
    {
        return view('admin.doctors.form', [
            'doctor' => new Doctor(['status' => ActiveStatus::Active]),
            'availableUsers' => $this->availableUsers(),
            'specializations' => $this->specializationOptions(),
        ]);
    }

    public function store(DoctorRequest $request): RedirectResponse
    {
        $doctor = Doctor::create($request->payload());

        $this->audit->created('doctor', 'Doctor', $doctor->id, "Menambahkan dokter {$doctor->name} ({$doctor->specialization}).");

        return redirect()->route('admin.doctors.index')->with('success', "Dokter {$doctor->name} berhasil ditambahkan.");
    }

    public function show(Doctor $doctor): View
    {
        $doctor->load(['user', 'schedules' => fn ($q) => $q->orderBy('day')]);

        return view('admin.doctors.show', [
            'doctor' => $doctor,
            'records' => $doctor->medicalRecords()->with('patient')->latest('examined_at')->limit(10)->get(),
            'recentQueues' => $doctor->queues()->with('patient')->latest('queue_date')->limit(8)->get(),
            'prescriptionCount' => $doctor->prescriptions()->count(),
        ]);
    }

    public function edit(Doctor $doctor): View
    {
        return view('admin.doctors.form', [
            'doctor' => $doctor,
            'availableUsers' => $this->availableUsers(),
            'specializations' => $this->specializationOptions(),
        ]);
    }

    /** Akun pengguna role dokter yang belum tertaut ke profil dokter. */
    private function availableUsers()
    {
        return User::query()
            ->where('role', UserRole::Doctor->value)
            ->whereDoesntHave('doctor')
            ->orderBy('name')
            ->get();
    }

    /** @return array<int, string> */
    private function specializationOptions(): array
    {
        return Doctor::query()
            ->distinct()
            ->orderBy('specialization')
            ->pluck('specialization')
            ->all();
    }

    public function update(DoctorRequest $request, Doctor $doctor): RedirectResponse
    {
        $doctor->update($request->payload());

        $this->audit->updated('doctor', 'Doctor', $doctor->id, "Memperbarui data dokter {$doctor->name}.");

        return redirect()->route('admin.doctors.index')->with('success', "Data dokter {$doctor->name} berhasil diperbarui.");
    }

    public function toggleStatus(Doctor $doctor): RedirectResponse
    {
        $status = $doctor->isActive() ? ActiveStatus::Inactive : ActiveStatus::Active;
        $doctor->update(['status' => $status]);

        $this->audit->updated('doctor', 'Doctor', $doctor->id, "Mengubah status dokter {$doctor->name} menjadi {$status->label()}.");

        return back()->with('success', "Status dokter {$doctor->name} berhasil diubah.");
    }

    public function destroy(Doctor $doctor): RedirectResponse
    {
        $name = $doctor->name;
        $doctor->delete();

        $this->audit->deleted('doctor', 'Doctor', $doctor->id, "Menghapus dokter {$name} beserta jadwal dan antreannya.");

        return redirect()->route('admin.doctors.index')->with('success', "Dokter {$name} telah dihapus.");
    }
}
