<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ActiveStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceRequest;
use App\Models\Service;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request): View
    {
        $services = Service::query()
            ->when($request->filled('q'), fn ($q) => $q->search($request->string('q')->trim()->value()))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')->value()))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->value()))
            ->orderBy('category')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.services.index', [
            'services' => $services,
            'categories' => Service::categoryOptions(),
            'statuses' => ActiveStatus::options(),
            'filters' => $request->only('q', 'category', 'status'),
        ]);
    }

    public function create(): View
    {
        return view('admin.services.form', [
            'service' => new Service(['status' => ActiveStatus::Active, 'unit' => 'Tindakan']),
            'categories' => Service::categoryOptions(),
            'units' => Service::unitOptions(),
            'statuses' => ActiveStatus::options(),
        ]);
    }

    public function store(ServiceRequest $request): RedirectResponse
    {
        $service = Service::create($request->payload());

        $this->audit->created('service', 'Service', $service->id, "Menambahkan tarif layanan {$service->name}.");

        return redirect()->route('admin.services.index')->with('success', "Tarif {$service->name} berhasil ditambahkan.");
    }

    public function show(Service $service): RedirectResponse
    {
        return redirect()->route('admin.services.edit', $service);
    }

    public function edit(Service $service): View
    {
        return view('admin.services.form', [
            'service' => $service,
            'categories' => Service::categoryOptions(),
            'units' => Service::unitOptions(),
            'statuses' => ActiveStatus::options(),
        ]);
    }

    public function update(ServiceRequest $request, Service $service): RedirectResponse
    {
        $service->update($request->payload());

        $this->audit->updated('service', 'Service', $service->id, "Memperbarui tarif layanan {$service->name}.");

        return redirect()->route('admin.services.index')->with('success', "Tarif {$service->name} berhasil diperbarui.");
    }

    public function toggleStatus(Service $service): RedirectResponse
    {
        $status = $service->status === ActiveStatus::Active ? ActiveStatus::Inactive : ActiveStatus::Active;
        $service->update(['status' => $status]);

        return back()->with('success', "Status tarif {$service->name} diubah menjadi {$status->label()}.");
    }

    public function destroy(Service $service): RedirectResponse
    {
        $name = $service->name;
        $service->delete();

        $this->audit->deleted('service', 'Service', $service->id, "Menghapus tarif layanan {$name}.");

        return redirect()->route('admin.services.index')->with('success', "Tarif {$name} telah dihapus.");
    }
}
