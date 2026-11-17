<?php

namespace App\Http\Controllers\Pharmacy;

use App\Enums\ActiveStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pharmacy\MedicineRequest;
use App\Models\Medicine;
use App\Services\AuditLogger;
use App\Services\MedicineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

class MedicineController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly MedicineService $medicines,
    ) {}

    public function index(Request $request): View
    {
        $medicines = Medicine::query()
            ->withCount('prescriptionDetails')
            ->when($request->filled('q'), fn ($q) => $q->search($request->string('q')->trim()->value()))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')->value))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->value))
            ->when($request->boolean('low_stock'), fn ($q) => $q->lowStock())
            ->when($request->string('expiry')->value === 'expired', fn ($q) => $q->expired())
            ->when($request->string('expiry')->value === 'expiring_soon', fn ($q) => $q->expiringSoon(30))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('pharmacy.medicines.index', [
            'medicines' => $medicines,
            'categories' => Medicine::categoryOptions(),
            'statuses' => ActiveStatus::options(),
            'filters' => $request->only('q', 'category', 'status', 'low_stock', 'expiry'),
            'lowStockCount' => Medicine::query()->active()->lowStock()->count(),
            'expiredCount' => Medicine::query()->active()->expired()->count(),
            'expiringSoonCount' => Medicine::query()->active()->expiringSoon(30)->count(),
            'expiredBatchCount' => $this->medicines->expiredBatchCount(),
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
        $batchNumber = $data['batch_number'] ?? null;
        $expiryDate = $data['expiry_date'] ?? null;

        unset($data['batch_number'], $data['stock']);

        try {
            $medicine = DB::transaction(function () use ($data, $stock, $batchNumber, $expiryDate) {
                $medicine = Medicine::create($data);

                if ($stock > 0) {
                    $this->medicines->stockIn(
                        $medicine,
                        $stock,
                        'Stok awal saat obat dibuat',
                        auth()->id(),
                        $expiryDate ? ($batchNumber ?: 'AWAL-'.$medicine->medicine_code) : null,
                        $expiryDate,
                    );
                }

                return $medicine;
            });
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
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
            'batches' => $medicine->batches()->orderBy('expiry_date')->orderBy('batch_number')->get(),
            'transactions' => $medicine->transactions()->with(['user', 'batch'])->latest()->paginate(15),
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
