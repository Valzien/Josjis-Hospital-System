<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ActiveStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StaffRequest;
use App\Models\Receptionist;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReceptionistController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request): View
    {
        $receptionists = Receptionist::query()
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->string('q')->trim().'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->value))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('admin.receptionists.index', [
            'receptionists' => $receptionists,
            'statuses' => ActiveStatus::options(),
            'filters' => $request->only('q', 'status'),
            'availableUsers' => User::query()->where('role', UserRole::Receptionist->value)->whereDoesntHave('receptionist')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.receptionists.form', ['receptionist' => new Receptionist(['status' => ActiveStatus::Active])]);
    }

    public function store(StaffRequest $request): RedirectResponse
    {
        $receptionist = Receptionist::create($request->payload());

        $this->audit->created('user', 'Receptionist', $receptionist->id, "Menambahkan resepsionis {$receptionist->name}.");

        return redirect()->route('admin.receptionists.index')->with('success', "Resepsionis {$receptionist->name} berhasil ditambahkan.");
    }

    public function show(Receptionist $receptionist): RedirectResponse
    {
        return redirect()->route('admin.receptionists.edit', $receptionist);
    }

    public function edit(Receptionist $receptionist): View
    {
        return view('admin.receptionists.form', ['receptionist' => $receptionist]);
    }

    public function update(StaffRequest $request, Receptionist $receptionist): RedirectResponse
    {
        $receptionist->update($request->payload());

        return redirect()->route('admin.receptionists.index')->with('success', "Data resepsionis {$receptionist->name} berhasil diperbarui.");
    }

    public function toggleStatus(Receptionist $receptionist): RedirectResponse
    {
        $status = $receptionist->status === ActiveStatus::Active ? ActiveStatus::Inactive : ActiveStatus::Active;
        $receptionist->update(['status' => $status]);

        return back()->with('success', "Status resepsionis {$receptionist->name} berhasil diubah menjadi {$status->label()}.");
    }

    public function destroy(Receptionist $receptionist): RedirectResponse
    {
        $name = $receptionist->name;
        $receptionist->delete();

        return redirect()->route('admin.receptionists.index')->with('success', "Resepsionis {$name} telah dihapus.");
    }
}
