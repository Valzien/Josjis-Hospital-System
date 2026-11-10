<?php

namespace App\Http\Controllers\Cashier;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cashier\PaymentRequest;
use App\Models\Invoice;
use App\Models\Queue;
use App\Services\AuditLogger;
use App\Services\BillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly BillingService $billing,
        private readonly AuditLogger $audit,
    ) {}

    public function index(Request $request): View
    {
        $invoices = Invoice::query()
            ->with(['patient', 'queue.doctor'])
            ->when($request->filled('q'), fn ($q) => $q->search($request->string('q')->trim()->value()))
            ->when($request->filled('status'), fn ($q) => $q->status($request->string('status')->value))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('created_at', $request->date('date')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('cashier.invoices.index', [
            'invoices' => $invoices,
            'statuses' => InvoiceStatus::options(),
            'filters' => $request->only('q', 'status', 'date'),
            'summary' => $this->billing->todaySummary(),
        ]);
    }

    public function pending(Request $request): View
    {
        $queues = Queue::query()
            ->with(['patient', 'doctor', 'medicalRecord.prescription'])
            ->whereHas('medicalRecord')
            ->whereDoesntHave('invoice')
            ->when($request->filled('q'), fn ($q) => $q->whereHas('patient', fn ($p) => $p->search($request->string('q')->trim()->value())))
            ->when($request->filled('date'), fn ($q) => $q->onDate($request->date('date')))
            ->latest('queue_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('cashier.invoices.pending', [
            'queues' => $queues,
            'filters' => $request->only('q', 'date'),
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $queue = Queue::query()
            ->with(['patient', 'doctor', 'medicalRecord.prescription.details.medicine', 'invoice'])
            ->findOrFail($request->integer('queue_id'));

        if ($queue->invoice !== null) {
            return redirect()->route('cashier.invoices.show', $queue->invoice)
                ->with('error', "Kunjungan ini sudah memiliki tagihan {$queue->invoice->code}.");
        }

        return view('cashier.invoices.create', [
            'queue' => $queue,
            'preview' => $this->billing->previewForQueue($queue),
            'consultationFee' => $this->billing->consultationFee(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'queue_id' => ['required', 'integer', 'exists:queues,id'],
            'discount' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [], [
            'queue_id' => 'kunjungan',
            'discount' => 'potongan',
            'notes' => 'catatan',
        ]);

        $queue = Queue::query()->with('patient')->findOrFail($data['queue_id']);

        try {
            $invoice = $this->billing->createForQueue(
                $queue,
                (float) ($data['discount'] ?? 0),
                $data['notes'] ?? null,
                auth()->id(),
            );
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $this->audit->created('invoice', 'Invoice', $invoice->id, "Membuat tagihan {$invoice->code} untuk {$queue->patient?->name} sebesar Rp ".number_format((float) $invoice->total, 0, ',', '.').'.');

        return redirect()->route('cashier.invoices.show', $invoice)
            ->with('success', "Tagihan {$invoice->code} berhasil dibuat.");
    }

    public function show(Invoice $invoice): View
    {
        $invoice->load(['patient', 'queue.doctor', 'medicalRecord', 'items', 'payments.receivedBy', 'issuedBy']);

        return view('cashier.invoices.show', [
            'invoice' => $invoice,
            'methods' => PaymentMethod::options(),
        ]);
    }

    public function storePayment(PaymentRequest $request, Invoice $invoice): RedirectResponse
    {
        try {
            $payment = $this->billing->recordPayment(
                $invoice,
                (float) $request->input('amount'),
                $request->string('method')->value(),
                $request->input('notes'),
                auth()->id(),
            );
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $this->audit->created('payment', 'Payment', $payment->id, "Menerima pembayaran {$payment->code} sebesar Rp ".number_format((float) $payment->amount, 0, ',', '.')." untuk tagihan {$invoice->code}.");

        return redirect()->route('cashier.invoices.show', $invoice)
            ->with('success', 'Pembayaran Rp '.number_format((float) $payment->amount, 0, ',', '.').' berhasil dicatat.');
    }

    public function cancel(Request $request, Invoice $invoice): RedirectResponse
    {
        try {
            $this->billing->cancel($invoice, $request->input('reason'));
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->audit->updated('invoice', 'Invoice', $invoice->id, "Membatalkan tagihan {$invoice->code}.");

        return redirect()->route('cashier.invoices.show', $invoice)
            ->with('success', "Tagihan {$invoice->code} telah dibatalkan.");
    }

    public function receipt(Invoice $invoice): View
    {
        $invoice->load(['patient', 'queue.doctor', 'items', 'payments.receivedBy', 'issuedBy']);

        return view('cashier.invoices.receipt', [
            'invoice' => $invoice,
        ]);
    }
}
