<?php

namespace App\Http\Controllers\Pharmacy;

use App\Enums\ActiveStatus;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pharmacy\MedicineRequest;
use App\Models\Medicine;
use App\Models\MedicineTransaction;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MedicineController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request): View
    {
        $medicines = Medicine::query()
            ->withCount('prescriptionDetails')
            ->when($request->filled('q'), fn ($q) => $q->search($request->string('q')->trim()->value()))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')->value))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->value))
            ->when($request->boolean('low_stock'), fn ($q) => $q->lowStock())
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('pharmacy.medicines.index', [
            'medicines' => $medicines,
            'categories' => Medicine::categoryOptions(),
            'statuses' => ActiveStatus::options(),
            'filters' => $request->only('q', 'category', 'status', 'low_stock'),
            'lowStockCount' => Medicine::query()->active()->lowStock()->count(),
        ]);
    }

    public function create(): View
    {
        return view('pharmacy.medicines.form', [
            'medicine' => new Medicine(['status' => ActiveStatus::Active, 'unit' => 'Tablet', 'minimum_stock' => 10]),
            'categories' => Medicine::categoryOptions(),
            'units' => Medicine::unitOptions(),
            'statuses' => ActiveStatus::options(),
        ]);
    }

    public function store(MedicineRequest $request): RedirectResponse
    {
        $data = $request->payload();
        $stock = (int) ($data['stock'] ?? 0);

        $medicine = Medicine::create($data);

        if ($stock > 0) {
            MedicineTransaction::create([
                'medicine_id' => $medicine->id,
                'type' => TransactionType::In,
                'quantity' => $stock,
                'stock_before' => 0,
                'stock_after' => $stock,
                'reference_type' => 'manual',
                'notes' => 'Stok awal saat obat dibuat',
                'user_id' => auth()->id(),
            ]);
        }

        $this->audit->created('pharmacy', 'Medicine', $medicine->id, "Menambahkan obat {$medicine->name} dengan stok awal {$stock}.");

        return redirect()->route('pharmacy.medicines.index')->with('success', "Obat {$medicine->name} berhasil ditambahkan.");
    }

    public function edit(Medicine $medicine): View
    {
        return view('pharmacy.medicines.form', [
            'medicine' => $medicine,
            'categories' => Medicine::categoryOptions(),
            'units' => Medicine::unitOptions(),
            'statuses' => ActiveStatus::options(),
        ]);
    }

    public function update(MedicineRequest $request, Medicine $medicine): RedirectResponse
    {
        $medicine->update($request->payload());

        $this->audit->updated('pharmacy', 'Medicine', $medicine->id, "Memperbarui data obat {$medicine->name}.");

        return redirect()->route('pharmacy.medicines.index')->with('success', "Data obat {$medicine->name} berhasil diperbarui.");
    }

    public function show(Medicine $medicine): View
    {
        $medicine->loadCount('prescriptionDetails');

        return view('pharmacy.medicines.show', [
            'medicine' => $medicine,
            'transactions' => $medicine->transactions()->with('user')->latest()->paginate(15),
        ]);
    }

    public function destroy(Medicine $medicine): RedirectResponse
    {
        $name = $medicine->name;
        $medicine->delete();

        $this->audit->deleted('pharmacy', 'Medicine', $medicine->id, "Menghapus obat {$name}.");

        return redirect()->route('pharmacy.medicines.index')->with('success', "Obat {$name} telah dihapus.");
    }
}
