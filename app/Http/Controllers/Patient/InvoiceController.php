<?php

namespace App\Http\Controllers\Patient;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $patient = auth()->user()->patient;

        $invoices = $patient->invoices()
            ->with(['queue.doctor'])
            ->when($request->filled('status'), fn ($q) => $q->status($request->string('status')->value))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('patient.invoices.index', [
            'invoices' => $invoices,
            'statuses' => InvoiceStatus::options(),
            'filters' => $request->only('status'),
            'outstanding' => (float) $patient->invoices()
                ->status(InvoiceStatus::openValues())
                ->sum('total') - (float) $patient->invoices()
                ->status(InvoiceStatus::openValues())
                ->sum('paid_amount'),
        ]);
    }

    public function show(Invoice $invoice): View
    {
        abort_if($invoice->patient_id !== auth()->user()->patient?->id, 403);

        $invoice->load(['items', 'payments', 'queue.doctor', 'medicalRecord']);

        return view('patient.invoices.show', [
            'invoice' => $invoice,
        ]);
    }
}
