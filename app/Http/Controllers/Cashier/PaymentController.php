<?php

namespace App\Http\Controllers\Cashier;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $payments = Payment::query()
            ->with(['invoice.patient', 'receivedBy'])
            ->when($request->filled('method'), fn ($q) => $q->where('method', $request->string('method')->value()))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('paid_at', $request->date('date')))
            ->latest('paid_at')
            ->paginate(25)
            ->withQueryString();

        return view('cashier.payments.index', [
            'payments' => $payments,
            'methods' => PaymentMethod::options(),
            'filters' => $request->only('method', 'date'),
            'total' => (float) Payment::query()
                ->when($request->filled('method'), fn ($q) => $q->where('method', $request->string('method')->value()))
                ->when($request->filled('date'), fn ($q) => $q->whereDate('paid_at', $request->date('date')), fn ($q) => $q->whereDate('paid_at', now()->toDateString()))
                ->sum('amount'),
        ]);
    }
}
