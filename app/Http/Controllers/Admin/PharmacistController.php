<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ActiveStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StaffRequest;
use App\Models\Pharmacist;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PharmacistController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request): View
    {
        $pharmacists = Pharmacist::query()
            ->withCount('processedPrescriptions')
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->string('q')->trim().'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->value))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('admin.pharmacists.index', [
            'pharmacists' => $pharmacists,
            'statuses' => ActiveStatus::options(),
            'filters' => $request->only('q', 'status'),
            'availableUsers' => User::query()->where('role', UserRole::Pharmacist->value)->whereDoesntHave('pharmacist')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.pharmacists.form', ['pharmacist' => new Pharmacist(['status' => ActiveStatus::Active])]);
    }

    public function store(StaffRequest $request): RedirectResponse
    {
        $pharmacist = Pharmacist::create($request->payload());

        $this->audit->created('pharmacy', 'Pharmacist', $pharmacist->id, "Menambahkan apoteker {$pharmacist->name}.");

        return redirect()->route('admin.pharmacists.index')->with('success', "Apoteker {$pharmacist->name} berhasil ditambahkan.");
    }

    public function show(Pharmacist $pharmacist): RedirectResponse
    {
        return redirect()->route('admin.pharmacists.edit', $pharmacist);
    }

    public function edit(Pharmacist $pharmacist): View
    {
        return view('admin.pharmacists.form', ['pharmacist' => $pharmacist]);
    }

    public function update(StaffRequest $request, Pharmacist $pharmacist): RedirectResponse
    {
        $pharmacist->update($request->payload());

        $this->audit->updated('pharmacy', 'Pharmacist', $pharmacist->id, "Memperbarui data apoteker {$pharmacist->name}.");

        return redirect()->route('admin.pharmacists.index')->with('success', "Data apoteker {$pharmacist->name} berhasil diperbarui.");
    }

    public function toggleStatus(Pharmacist $pharmacist): RedirectResponse
    {
        $status = $pharmacist->status === ActiveStatus::Active ? ActiveStatus::Inactive : ActiveStatus::Active;
        $pharmacist->update(['status' => $status]);

        return back()->with('success', "Status apoteker {$pharmacist->name} berhasil diubah menjadi {$status->label()}.");
    }

    public function destroy(Pharmacist $pharmacist): RedirectResponse
    {
        $name = $pharmacist->name;
        $pharmacist->delete();

        $this->audit->deleted('pharmacy', 'Pharmacist', $pharmacist->id, "Menghapus apoteker {$name}.");

        return redirect()->route('admin.pharmacists.index')->with('success', "Apoteker {$name} telah dihapus.");
    }

    /** Rekap transaksi obat per apoteker (ringkasan tambahan). */
    public function workload(): array
    {
        return DB::table('prescriptions')
            ->whereNotNull('processed_by')
            ->selectRaw('processed_by, count(*) as total')
            ->groupBy('processed_by')
            ->pluck('total', 'processed_by')
            ->all();
    }
}
