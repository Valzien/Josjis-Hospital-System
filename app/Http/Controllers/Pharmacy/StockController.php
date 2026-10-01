<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pharmacy\AdjustStockRequest;
use App\Http\Requests\Pharmacy\StockRequest;
use App\Models\Medicine;
use App\Services\AuditLogger;
use App\Services\MedicineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class StockController extends Controller
{
    public function __construct(
        private readonly MedicineService $medicines,
        private readonly AuditLogger $audit,
    ) {}

    public function index(Request $request): View
    {
        $medicines = Medicine::query()
            ->withCount('transactions')
            ->when($request->filled('q'), fn ($q) => $q->search($request->string('q')->trim()->value()))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')->value))
            ->when($request->boolean('low_stock'), fn ($q) => $q->lowStock())
            ->when($request->boolean('out_of_stock'), fn ($q) => $q->where('stock', '<=', 0))
            ->orderBy('stock')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('pharmacy.stock.index', [
            'medicines' => $medicines,
            'categories' => Medicine::categoryOptions(),
            'filters' => $request->only('q', 'category', 'low_stock', 'out_of_stock'),
            'lowStock' => $this->medicines->lowStockMedicines(10),
            'stats' => [
                'total_types' => Medicine::query()->active()->count(),
                'low_stock' => $this->medicines->lowStockCount(),
                'out_of_stock' => Medicine::query()->active()->where('stock', '<=', 0)->count(),
                'value' => 'Rp '.number_format($this->medicines->inventoryValue(), 0, ',', '.'),
            ],
        ]);
    }

    public function stockIn(StockRequest $request, Medicine $medicine): RedirectResponse
    {
        try {
            $this->medicines->stockIn(
                $medicine,
                $request->integer('quantity'),
                $request->input('notes') ?: 'Barang masuk',
                auth()->id(),
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->audit->created('pharmacy', 'MedicineTransaction', $medicine->id, "Barang masuk {$request->integer('quantity')} {$medicine->unit} {$medicine->name}.");

        return back()->with('success', "Stok {$medicine->name} berhasil ditambahkan.");
    }

    public function stockOut(StockRequest $request, Medicine $medicine): RedirectResponse
    {
        try {
            $this->medicines->stockOut(
                $medicine,
                $request->integer('quantity'),
                $request->input('notes') ?: 'Barang keluar',
                auth()->id(),
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->audit->deleted('pharmacy', 'MedicineTransaction', $medicine->id, "Barang keluar {$request->integer('quantity')} {$medicine->unit} {$medicine->name}.");

        return back()->with('success', "Stok {$medicine->name} berhasil dikurangi.");
    }

    public function adjust(AdjustStockRequest $request, Medicine $medicine): RedirectResponse
    {
        try {
            $this->medicines->adjustStock(
                $medicine,
                $request->integer('new_stock'),
                $request->input('notes') ?: 'Penyesuaian stok opname',
                auth()->id(),
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->audit->updated('pharmacy', 'MedicineTransaction', $medicine->id, "Penyesuaian stok {$medicine->name} menjadi {$request->integer('new_stock')}.");

        return back()->with('success', "Stok {$medicine->name} berhasil disesuaikan.");
    }
}
