<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevenueReportTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function filters(array $overrides = []): array
    {
        return array_merge([
            'from' => now()->subDays(6)->toDateString(),
            'to' => now()->toDateString(),
            'doctor_id' => null,
            'patient_id' => null,
            'medicine_id' => null,
            'status' => null,
            'method' => null,
            'q' => null,
        ], $overrides);
    }

    public function test_invoice_and_payment_reports_return_rows_and_totals(): void
    {
        $partial = Invoice::factory()->partial(40000)->create([
            'total' => 100000,
            'subtotal' => 100000,
        ]);

        $paid = Invoice::factory()->create([
            'status' => InvoiceStatus::Paid->value,
            'total' => 50000,
            'subtotal' => 50000,
            'paid_amount' => 50000,
            'paid_at' => now(),
        ]);

        Payment::factory()->create([
            'invoice_id' => $partial->id,
            'amount' => 40000,
            'method' => PaymentMethod::Cash->value,
            'paid_at' => now(),
        ]);

        Payment::factory()->create([
            'invoice_id' => $paid->id,
            'amount' => 50000,
            'method' => PaymentMethod::Transfer->value,
            'paid_at' => now(),
        ]);

        $reports = app(ReportService::class);
        $filters = $this->filters();

        $invoiceRows = $reports->rows('invoices', $filters);
        $this->assertCount(2, $invoiceRows);

        $invoiceTotals = $reports->totals('invoices', $filters);
        $this->assertSame(2, $invoiceTotals['total_tagihan']);
        $this->assertSame('Rp 150.000', $invoiceTotals['total_nilai']);
        $this->assertSame('Rp 90.000', $invoiceTotals['total_dibayar']);
        $this->assertSame('Rp 60.000', $invoiceTotals['total_sisa']);

        $paymentRows = $reports->rows('payments', $filters);
        $this->assertCount(2, $paymentRows);

        $paymentTotals = $reports->totals('payments', $filters);
        $this->assertSame(2, $paymentTotals['total_pembayaran']);
        $this->assertSame('Rp 90.000', $paymentTotals['total_pendapatan']);
    }

    public function test_payment_report_can_be_filtered_by_method(): void
    {
        $invoice = Invoice::factory()->create(['total' => 100000, 'subtotal' => 100000]);

        Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 30000,
            'method' => PaymentMethod::Cash->value,
        ]);

        Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 70000,
            'method' => PaymentMethod::Qris->value,
        ]);

        $rows = app(ReportService::class)->rows('payments', $this->filters(['method' => PaymentMethod::Qris->value]));

        $this->assertCount(1, $rows);
        $this->assertSame(PaymentMethod::Qris->label(), $rows[0][4]);
        $this->assertSame('Rp 70.000', $rows[0][5]);
    }

    public function test_revenue_summary_and_series_follow_payments(): void
    {
        $invoice = Invoice::factory()->partial(40000)->create([
            'total' => 100000,
            'subtotal' => 100000,
        ]);

        Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 40000,
            'paid_at' => now()->startOfDay()->addHours(9),
        ]);

        $reports = app(ReportService::class);
        $filters = $this->filters();

        $summary = $reports->summary($filters);
        $this->assertSame(1, $summary['invoices']);
        $this->assertSame(1, $summary['payments']);
        $this->assertEqualsWithDelta(40000, $summary['revenue'], 0.01);
        $this->assertEqualsWithDelta(60000, $summary['outstanding'], 0.01);

        $series = $reports->revenueSeries($filters);
        $this->assertCount(7, $series['labels']);
        $this->assertEqualsWithDelta(40000, array_sum($series['values']), 0.01);
    }

    public function test_report_pages_render_with_finance_data(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $invoice = Invoice::factory()->paid()->create(['total' => 50000, 'subtotal' => 50000]);
        Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 50000,
            'method' => PaymentMethod::Cash->value,
        ]);

        $this->get(route('admin.reports.index'))->assertOk()->assertSee('Pendapatan Diterima');

        $this->get(route('admin.reports.show', ['report' => 'invoices']))
            ->assertOk()
            ->assertSee($invoice->code)
            ->assertSee('Status Tagihan');

        $this->get(route('admin.reports.show', ['report' => 'payments']))
            ->assertOk()
            ->assertSee('Metode');

        $this->get(route('admin.reports.export', ['report' => 'invoices', 'format' => 'csv']))
            ->assertOk();
    }

    public function test_reports_route_is_admin_only(): void
    {
        $this->actingAs(User::factory()->doctor()->create())
            ->get(route('admin.reports.show', ['report' => 'invoices']))
            ->assertForbidden();
    }
}
