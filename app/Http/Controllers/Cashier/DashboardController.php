<?php

namespace App\Http\Controllers\Cashier;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\BillingService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(BillingService $billing): View
    {
        return view('cashier.dashboard', [
            'summary' => $billing->todaySummary(),
            'unpaidInvoices' => Invoice::query()
                ->with(['patient', 'queue'])
                ->status(InvoiceStatus::openValues())
                ->latest()
                ->limit(8)
                ->get(),
            'recentPayments' => Payment::query()
                ->with(['invoice.patient', 'receivedBy'])
                ->latest('paid_at')
                ->limit(8)
                ->get(),
        ]);
    }
}
