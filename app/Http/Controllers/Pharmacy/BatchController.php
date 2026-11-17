<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pharmacy\DisposeBatchRequest;
use App\Models\MedicineBatch;
use App\Services\AuditLogger;
use App\Services\MedicineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class BatchController extends Controller
{
    public function __construct(
        private readonly MedicineService $medicines,
        private readonly AuditLogger $audit,
    ) {}

    public function index(Request $request): View
    {
        $status = $request->string('status')->value();

        $batches = MedicineBatch::query()
            ->with('medicine')
            ->where('quantity', '>', 0)
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q')->trim().'%';
                $query->where(function ($q) use ($term) {
                    $q->where('batch_number', 'like', $term)
                        ->orWhereHas('medicine', fn ($m) => $m->where('name', 'like', $term)
                            ->orWhere('medicine_code', 'like', $term));
                });
            })
            ->when($status === 'expired', fn ($q) => $q->expired())
            ->when($status === 'expiring_soon', fn ($q) => $q->expiringSoon(30))
            ->when($status === 'safe', fn ($q) => $q->where(function ($q) {
                $q->whereNull('expiry_date')
                    ->orWhereDate('expiry_date', '>=', now()->addDays(30)->toDateString());
            }))
            ->orderByRaw('expiry_date IS NULL, expiry_date ASC')
            ->orderBy('batch_number')
            ->paginate(20)
            ->withQueryString();

        return view('pharmacy.batches.index', [
            'batches' => $batches,
            'filters' => $request->only('q', 'status'),
            'stats' => [
                'active' => MedicineBatch::query()->available()->count(),
                'expired' => $this->medicines->expiredBatchCount(),
                'expiring' => $this->medicines->expiringBatchCount(),
                'expired_qty' => $this->medicines->expiredBatchQuantity(),
            ],
        ]);
    }

    public function dispose(DisposeBatchRequest $request, MedicineBatch $batch): RedirectResponse
    {
        $batchNumber = $batch->batch_number;
        $medicineName = $batch->medicine?->name ?? '-';

        try {
            $this->medicines->disposeExpiredBatch($batch, auth()->id());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->audit->deleted('pharmacy', 'MedicineBatch', $batch->id, "Memusnahkan batch kedaluwarsa {$batchNumber} ({$medicineName}).");

        return back()->with('success', "Batch kedaluwarsa {$batchNumber} telah dimusnahkan.");
    }
}
