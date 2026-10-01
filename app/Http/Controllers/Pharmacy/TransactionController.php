<?php

namespace App\Http\Controllers\Pharmacy;

use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Models\Medicine;
use App\Models\MedicineTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $transactions = MedicineTransaction::query()
            ->with(['medicine', 'user'])
            ->when($request->filled('q'), fn ($q) => $q->search($request->string('q')->trim()->value()))
            ->when($request->filled('type'), fn ($q) => $q->type($request->string('type')->value))
            ->when($request->filled('medicine_id'), fn ($q) => $q->where('medicine_id', $request->integer('medicine_id')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('to')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $from = $request->date('from') ?? now()->subDays(29)->startOfDay();
        $to = $request->date('to') ?? now()->endOfDay();

        return view('pharmacy.transactions.index', [
            'transactions' => $transactions,
            'types' => TransactionType::options(),
            'medicines' => Medicine::query()->orderBy('name')->get(),
            'filters' => $request->only('q', 'type', 'medicine_id', 'from', 'to'),
            'stats' => [
                'total' => MedicineTransaction::query()->whereBetween('created_at', [$from, $to])->count(),
                'in' => (int) MedicineTransaction::query()->where('type', TransactionType::In->value)->whereBetween('created_at', [$from, $to])->sum(DB::raw('ABS(quantity)')),
                'out' => (int) MedicineTransaction::query()->where('type', TransactionType::Out->value)->whereBetween('created_at', [$from, $to])->sum(DB::raw('ABS(quantity)')),
                'adjustment' => MedicineTransaction::query()->where('type', TransactionType::Adjustment->value)->whereBetween('created_at', [$from, $to])->count(),
            ],
            'dailyChart' => $this->dailyChart($from, $to),
        ]);
    }

    /** @return array{labels: array<int, string>, inbound: array<int, int>, outbound: array<int, int>} */
    private function dailyChart($from, $to): array
    {
        $expr = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m-%d', created_at)"
            : "DATE_FORMAT(created_at, '%Y-%m-%d')";

        $grouped = MedicineTransaction::query()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("{$expr} as d, type, SUM(ABS(quantity)) as total")
            ->groupBy('d', 'type')
            ->get();

        $rows = [];
        foreach ($grouped as $row) {
            $rows[$row->d.'|'.$row->type] = (float) $row->total;
        }

        $labels = [];
        $inbound = [];
        $outbound = [];
        $cursor = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->startOfDay();

        while ($cursor->lessThanOrEqualTo($end) && count($labels) < 90) {
            $key = $cursor->toDateString();
            $labels[] = $cursor->format('d/m');
            $inbound[] = (int) ($rows[$key.'|'.TransactionType::In->value] ?? 0);
            $outbound[] = (int) ($rows[$key.'|'.TransactionType::Out->value] ?? 0);
            $cursor->addDay();
        }

        return ['labels' => $labels, 'inbound' => $inbound, 'outbound' => $outbound];
    }
}
