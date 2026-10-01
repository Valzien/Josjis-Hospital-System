<?php

namespace App\Http\Controllers\Pharmacy;

use App\Enums\PrescriptionStatus;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Models\Medicine;
use App\Models\MedicineTransaction;
use App\Models\Prescription;
use App\Services\MedicineService;
use App\Services\PrescriptionService;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(PrescriptionService $prescriptions, MedicineService $medicines): View
    {
        $today = now()->toDateString();
        $summary = $prescriptions->statusSummary();

        return view('pharmacy.dashboard', [
            'summary' => $summary,
            'pendingList' => Prescription::query()
                ->with(['patient', 'doctor'])
                ->whereIn('status', [PrescriptionStatus::Pending->value, PrescriptionStatus::Processing->value, PrescriptionStatus::Ready->value])
                ->orderBy('created_at')
                ->limit(8)
                ->get(),
            'recentCompleted' => Prescription::query()
                ->with(['patient', 'doctor', 'processedBy'])
                ->where('status', PrescriptionStatus::Completed->value)
                ->latest('processed_at')
                ->limit(5)
                ->get(),
            'lowStock' => $medicines->lowStockMedicines(8),
            'lowStockCount' => $medicines->lowStockCount(),
            'totalMedicines' => Medicine::query()->active()->count(),
            'todayTransactions' => MedicineTransaction::query()
                ->with('medicine')
                ->whereDate('created_at', $today)
                ->count(),
            'todayOut' => MedicineTransaction::query()
                ->where('type', TransactionType::Out->value)
                ->whereDate('created_at', $today)
                ->sum(DB::raw('ABS(quantity)')),
            'pendingCount' => $summary[PrescriptionStatus::Pending->value] ?? 0,
            'processingCount' => $summary[PrescriptionStatus::Processing->value] ?? 0,
            'readyCount' => $summary[PrescriptionStatus::Ready->value] ?? 0,
            'completedToday' => Prescription::query()
                ->where('status', PrescriptionStatus::Completed->value)
                ->whereDate('processed_at', $today)
                ->count(),
            'inventoryValue' => $medicines->inventoryValue(),
            'recentTransactions' => MedicineTransaction::query()
                ->with(['medicine', 'user'])
                ->latest()
                ->limit(6)
                ->get(),
        ]);
    }
}
