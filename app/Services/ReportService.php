<?php

namespace App\Services;

use App\Enums\AdmissionStatus;
use App\Enums\InvoiceStatus;
use App\Enums\QueueStatus;
use App\Enums\TransactionType;
use App\Models\Admission;
use App\Models\AdmissionCharge;
use App\Models\Doctor;
use App\Models\Invoice;
use App\Models\MedicalRecord;
use App\Models\Medicine;
use App\Models\MedicineTransaction;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Prescription;
use App\Models\Queue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Laporan administrasi (brief §19).
 *
 * Setiap laporanreturns kumpulan baris homogen agar bisa langsung
 * diekspor ke CSV/PDF oleh ReportExportController.
 */
class ReportService
{
    /** @return array<string, string> */
    public static function availableReports(): array
    {
        return [
            'patients' => 'Laporan Pasien',
            'queues' => 'Laporan Antrean',
            'examinations' => 'Laporan Pemeriksaan',
            'prescriptions' => 'Laporan Resep',
            'medicines' => 'Laporan Obat',
            'transactions' => 'Laporan Transaksi Obat',
            'invoices' => 'Laporan Tagihan',
            'payments' => 'Laporan Pembayaran',
            'admissions' => 'Laporan Rawat Inap',
        ];
    }

    public static function isAvailable(string $report): bool
    {
        return array_key_exists($report, self::availableReports());
    }

    public static function title(string $report): string
    {
        return self::availableReports()[$report] ?? 'Laporan';
    }

    /** @return array<int, string> */
    public static function columns(string $report): array
    {
        return match ($report) {
            'patients' => ['No. RM', 'Nama', 'NIK', 'Jenis Kelamin', 'Usia', 'Telepon', 'Alamat', 'Terdaftar'],
            'queues' => ['Nomor Antrean', 'Tanggal', 'Pasien', 'Dokter', 'Status', 'Dipanggil', 'Selesai', 'Dibuat Oleh'],
            'examinations' => ['No. Rekam', 'Tanggal', 'Pasien', 'Dokter', 'Keluhan', 'Diagnosis', 'Resep?'],
            'prescriptions' => ['Kode Resep', 'Tanggal', 'Pasien', 'Dokter', 'Status', 'Jumlah Item', 'Total'],
            'medicines' => ['Kode', 'Nama Obat', 'Kategori', 'Satuan', 'Stok', 'Stok Min', 'Harga', 'Nilai'],
            'transactions' => ['Waktu', 'Kode', 'Obat', 'Tipe', 'Jumlah', 'Stok Sebelum', 'Stok Sesudah', 'Referensi', 'Petugas'],
            'invoices' => ['Kode', 'Tanggal', 'Pasien', 'Dokter', 'Status', 'Subtotal', 'Potongan', 'Total', 'Dibayar', 'Sisa'],
            'payments' => ['Kode', 'Waktu', 'Tagihan', 'Pasien', 'Metode', 'Nominal', 'Kasir'],
            'admissions' => ['Kode', 'Masuk', 'Pasien', 'Dokter', 'Kamar', 'Tempat Tidur', 'Status', 'Lama Inap', 'Pulang', 'Total Biaya'],
            default => [],
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function summary(array $filters): array
    {
        $from = $filters['from'];
        $to = $filters['to'];

        return [
            'patients' => Patient::query()->whereBetween('created_at', [$from, $to.' 23:59:59'])->count(),
            'queues' => Queue::query()->whereBetween('queue_date', [$from, $to])->count(),
            'examinations' => MedicalRecord::query()->whereBetween('examined_at', [$from, $to.' 23:59:59'])->count(),
            'prescriptions' => Prescription::query()->whereBetween('created_at', [$from, $to.' 23:59:59'])->count(),
            'medicines_out' => MedicineTransaction::query()->where('type', TransactionType::Out->value)->whereBetween('created_at', [$from, $to.' 23:59:59'])->count(),
            'medicines_in' => MedicineTransaction::query()->where('type', TransactionType::In->value)->whereBetween('created_at', [$from, $to.' 23:59:59'])->count(),
            'revenue' => (float) Payment::query()->whereBetween('paid_at', [$from, $to.' 23:59:59'])->sum('amount'),
            'invoices' => Invoice::query()->whereBetween('created_at', [$from, $to.' 23:59:59'])->count(),
            'payments' => Payment::query()->whereBetween('paid_at', [$from, $to.' 23:59:59'])->count(),
            'outstanding' => (float) Invoice::query()
                ->whereIn('status', InvoiceStatus::openValues())
                ->whereBetween('created_at', [$from, $to.' 23:59:59'])
                ->sum(DB::raw('total - paid_amount')),
            'admissions' => Admission::query()->whereBetween('admitted_at', [$from, $to.' 23:59:59'])->count(),
            'admissions_active' => Admission::query()->where('status', AdmissionStatus::Active->value)->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function rows(string $report, array $filters): array
    {
        return match ($report) {
            'patients' => $this->patientRows($filters),
            'queues' => $this->queueRows($filters),
            'examinations' => $this->examinationRows($filters),
            'prescriptions' => $this->prescriptionRows($filters),
            'medicines' => $this->medicineRows($filters),
            'transactions' => $this->transactionRows($filters),
            'invoices' => $this->invoiceRows($filters),
            'payments' => $this->paymentRows($filters),
            'admissions' => $this->admissionRows($filters),
            default => [],
        };
    }

    /** @return array<int, array<string, mixed>> */
    private function patientRows(array $filters): array
    {
        return Patient::query()
            ->whereBetween('created_at', [$filters['from'], $filters['to'].' 23:59:59'])
            ->when($filters['q'], fn ($q) => $q->search($filters['q']))
            ->orderBy('created_at')
            ->get()
            ->map(fn (Patient $p) => [
                $p->medical_record_number,
                $p->name,
                $p->nik ?? '-',
                $p->genderLabel(),
                $p->age() ? $p->age().' th' : '-',
                $p->phone ?? '-',
                Str::limit((string) $p->address, 40),
                $p->created_at->format('d/m/Y'),
            ])->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function queueRows(array $filters): array
    {
        return Queue::query()
            ->with(['patient', 'doctor', 'createdBy'])
            ->whereBetween('queue_date', [$filters['from'], $filters['to']])
            ->when($filters['doctor_id'], fn ($q) => $q->where('doctor_id', $filters['doctor_id']))
            ->when($filters['patient_id'], fn ($q) => $q->where('patient_id', $filters['patient_id']))
            ->when($filters['status'], fn ($q) => $q->where('status', $filters['status']))
            ->when($filters['q'], fn ($q) => $q->whereHas('patient', fn ($p) => $p->search($filters['q'])))
            ->orderBy('queue_date')
            ->orderBy('queue_number')
            ->get()
            ->map(fn (Queue $q) => [
                $q->queue_number,
                $q->queue_date->format('d/m/Y'),
                $q->patient?->name ?? '-',
                $q->doctor?->name ?? '-',
                $q->statusLabel(),
                $q->called_at?->format('d/m/Y H:i') ?? '-',
                $q->completed_at?->format('d/m/Y H:i') ?? '-',
                $q->createdBy?->name ?? 'Pasien (online)',
            ])->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function examinationRows(array $filters): array
    {
        return MedicalRecord::query()
            ->with(['patient', 'doctor', 'prescription'])
            ->whereBetween('examined_at', [$filters['from'], $filters['to'].' 23:59:59'])
            ->when($filters['doctor_id'], fn ($q) => $q->where('doctor_id', $filters['doctor_id']))
            ->when($filters['patient_id'], fn ($q) => $q->where('patient_id', $filters['patient_id']))
            ->when($filters['q'], fn ($q) => $q->search($filters['q']))
            ->orderBy('examined_at')
            ->get()
            ->map(fn (MedicalRecord $r) => [
                $r->record_number,
                $r->examined_at?->format('d/m/Y H:i') ?? '-',
                $r->patient?->name ?? '-',
                $r->doctor?->name ?? '-',
                Str::limit((string) $r->complaint, 45),
                Str::limit((string) $r->diagnosis, 45),
                $r->prescription ? $r->prescription->code : '-',
            ])->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function prescriptionRows(array $filters): array
    {
        return Prescription::query()
            ->with(['patient', 'doctor'])
            ->withCount('details')
            ->whereBetween('created_at', [$filters['from'], $filters['to'].' 23:59:59'])
            ->when($filters['doctor_id'], fn ($q) => $q->where('doctor_id', $filters['doctor_id']))
            ->when($filters['patient_id'], fn ($q) => $q->where('patient_id', $filters['patient_id']))
            ->when($filters['status'], fn ($q) => $q->where('status', $filters['status']))
            ->when($filters['q'], fn ($q) => $q->search($filters['q']))
            ->orderBy('created_at')
            ->get()
            ->map(fn (Prescription $p) => [
                $p->code,
                $p->created_at->format('d/m/Y H:i'),
                $p->patient?->name ?? '-',
                $p->doctor?->name ?? '-',
                $p->statusLabel(),
                $p->details_count,
                'Rp '.number_format((float) $p->total_price, 0, ',', '.'),
            ])->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function medicineRows(array $filters): array
    {
        return Medicine::query()
            ->when($filters['medicine_id'], fn ($q) => $q->where('id', $filters['medicine_id']))
            ->when($filters['q'], fn ($q) => $q->search($filters['q']))
            ->orderBy('name')
            ->get()
            ->map(fn (Medicine $m) => [
                $m->medicine_code,
                $m->name,
                $m->category,
                $m->unit,
                $m->stock,
                $m->minimum_stock,
                'Rp '.number_format((float) $m->price, 0, ',', '.'),
                'Rp '.number_format($m->stock * (float) $m->price, 0, ',', '.'),
            ])->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function transactionRows(array $filters): array
    {
        return MedicineTransaction::query()
            ->with(['medicine', 'user'])
            ->whereBetween('created_at', [$filters['from'], $filters['to'].' 23:59:59'])
            ->when($filters['medicine_id'], fn ($q) => $q->where('medicine_id', $filters['medicine_id']))
            ->when($filters['status'], fn ($q) => $q->where('type', $filters['status']))
            ->when($filters['q'], fn ($q) => $q->search($filters['q']))
            ->orderBy('created_at')
            ->get()
            ->map(fn (MedicineTransaction $t) => [
                $t->created_at->format('d/m/Y H:i'),
                $t->medicine?->medicine_code ?? '-',
                $t->medicine?->name ?? '-',
                $t->typeLabel(),
                $t->quantity > 0 ? '+'.$t->quantity : $t->quantity,
                $t->stock_before,
                $t->stock_after,
                $t->referenceLabel(),
                $t->user?->name ?? 'Sistem',
            ])->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function invoiceRows(array $filters): array
    {
        return $this->invoiceQuery($filters)
            ->with(['patient', 'queue.doctor', 'admission.doctor'])
            ->orderBy('created_at')
            ->get()
            ->map(fn (Invoice $invoice) => [
                $invoice->code,
                $invoice->created_at->format('d/m/Y H:i'),
                $invoice->patient?->name ?? '-',
                $invoice->queue?->doctor?->name ?? $invoice->admission?->doctor?->name ?? '-',
                $invoice->statusLabel(),
                $this->rupiah((float) $invoice->subtotal),
                $this->rupiah((float) $invoice->discount),
                $this->rupiah((float) $invoice->total),
                $this->rupiah((float) $invoice->paid_amount),
                $this->rupiah($invoice->remainingAmount()),
            ])->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function admissionRows(array $filters): array
    {
        return $this->admissionQuery($filters)
            ->with(['patient', 'doctor', 'room', 'bed'])
            ->withSum('charges as charges_total', 'subtotal')
            ->orderBy('admitted_at')
            ->get()
            ->map(fn (Admission $admission) => [
                $admission->code,
                $admission->admitted_at?->format('d/m/Y H:i') ?? '-',
                $admission->patient?->name ?? '-',
                $admission->doctor?->name ?? '-',
                $admission->room?->name ?? '-',
                $admission->bed?->code ?? '-',
                $admission->statusLabel(),
                $admission->dayCount().' hari',
                $admission->discharged_at?->format('d/m/Y H:i') ?? '-',
                $this->rupiah((float) ($admission->charges_total ?? 0)),
            ])->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function paymentRows(array $filters): array
    {
        return $this->paymentQuery($filters)
            ->with(['invoice.patient', 'invoice.queue.doctor', 'receivedBy'])
            ->orderBy('paid_at')
            ->get()
            ->map(fn (Payment $payment) => [
                $payment->code,
                $payment->paid_at?->format('d/m/Y H:i') ?? '-',
                $payment->invoice?->code ?? '-',
                $payment->invoice?->patient?->name ?? '-',
                $payment->methodLabel(),
                $this->rupiah((float) $payment->amount),
                $payment->receivedBy?->name ?? '-',
            ])->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Invoice>
     */
    private function invoiceQuery(array $filters): Builder
    {
        return Invoice::query()
            ->whereBetween('created_at', [$filters['from'], $filters['to'].' 23:59:59'])
            ->when($filters['patient_id'] ?? null, fn ($q) => $q->where('patient_id', $filters['patient_id']))
            ->when($filters['doctor_id'] ?? null, fn ($q) => $q->where(function (Builder $qq) use ($filters) {
                $qq->whereHas('queue', fn (Builder $queue) => $queue->where('doctor_id', $filters['doctor_id']))
                    ->orWhereHas('admission', fn (Builder $admission) => $admission->where('doctor_id', $filters['doctor_id']));
            }))
            ->when($filters['status'] ?? null, fn ($q) => $q->status($filters['status']))
            ->when($filters['q'] ?? null, fn ($q) => $q->search($filters['q']));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Payment>
     */
    private function paymentQuery(array $filters): Builder
    {
        return Payment::query()
            ->whereBetween('paid_at', [$filters['from'], $filters['to'].' 23:59:59'])
            ->when($filters['method'] ?? null, fn ($q) => $q->where('method', $filters['method']))
            ->when($filters['patient_id'] ?? null, fn ($q) => $q->whereHas('invoice', fn ($i) => $i->where('patient_id', $filters['patient_id'])))
            ->when($filters['doctor_id'] ?? null, fn ($q) => $q->whereHas('invoice', fn (Builder $i) => $i->where(function (Builder $ii) use ($filters) {
                $ii->whereHas('queue', fn (Builder $queue) => $queue->where('doctor_id', $filters['doctor_id']))
                    ->orWhereHas('admission', fn (Builder $admission) => $admission->where('doctor_id', $filters['doctor_id']));
            })))
            ->when($filters['q'] ?? null, fn ($q) => $q->where(function (Builder $qq) use ($filters) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $filters['q']).'%';
                $qq->where('code', 'like', $like)
                    ->orWhereHas('invoice', fn (Builder $i) => $i->search($filters['q']));
            }));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Admission>
     */
    private function admissionQuery(array $filters): Builder
    {
        return Admission::query()
            ->whereBetween('admitted_at', [$filters['from'], $filters['to'].' 23:59:59'])
            ->when($filters['patient_id'] ?? null, fn ($q) => $q->where('patient_id', $filters['patient_id']))
            ->when($filters['doctor_id'] ?? null, fn ($q) => $q->where('doctor_id', $filters['doctor_id']))
            ->when($filters['status'] ?? null, fn ($q) => $q->where('status', $filters['status']))
            ->when($filters['q'] ?? null, fn ($q) => $q->search($filters['q']));
    }

    private function rupiah(float $value): string
    {
        return 'Rp '.number_format($value, 0, ',', '.');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function totals(string $report, array $filters): array
    {
        $rows = $this->rows($report, $filters);

        return match ($report) {
            'medicines' => [
                'total_obat' => count($rows),
                'total_stok' => array_sum(array_column($rows, 4)),
                'total_nilai' => 'Rp '.number_format(array_sum(array_map(
                    fn ($r) => (float) preg_replace('/[^0-9]/', '', (string) $r[7]),
                    $rows
                )), 0, ',', '.'),
            ],
            'prescriptions' => [
                'total_resep' => count($rows),
                'total_nilai' => 'Rp '.number_format(array_sum(array_map(
                    fn ($r) => (float) preg_replace('/[^0-9]/', '', (string) $r[6]),
                    $rows
                )), 0, ',', '.'),
            ],
            'transactions' => [
                'total_transaksi' => count($rows),
                'total_masuk' => MedicineTransaction::query()->where('type', TransactionType::In->value)
                    ->whereBetween('created_at', [$filters['from'], $filters['to'].' 23:59:59'])->sum(DB::raw('ABS(quantity)')),
                'total_keluar' => MedicineTransaction::query()->where('type', TransactionType::Out->value)
                    ->whereBetween('created_at', [$filters['from'], $filters['to'].' 23:59:59'])->sum(DB::raw('ABS(quantity)')),
            ],
            'queues' => [
                'total_antrean' => count($rows),
                'total_selesai' => Queue::query()->whereBetween('queue_date', [$filters['from'], $filters['to']])
                    ->where('status', QueueStatus::Completed->value)->count(),
            ],
            'invoices' => [
                'total_tagihan' => count($rows),
                'total_nilai' => $this->rupiah((float) $this->invoiceQuery($filters)->sum('total')),
                'total_dibayar' => $this->rupiah((float) $this->invoiceQuery($filters)->sum('paid_amount')),
                'total_sisa' => $this->rupiah((float) $this->invoiceQuery($filters)->sum(DB::raw('total - paid_amount'))),
            ],
            'payments' => [
                'total_pembayaran' => count($rows),
                'total_pendapatan' => $this->rupiah((float) $this->paymentQuery($filters)->sum('amount')),
            ],
            'admissions' => [
                'total_rawat_inap' => count($rows),
                'total_dirawat' => Admission::query()
                    ->whereBetween('admitted_at', [$filters['from'], $filters['to'].' 23:59:59'])
                    ->where('status', AdmissionStatus::Active->value)
                    ->count(),
                'total_pulang' => Admission::query()
                    ->whereBetween('admitted_at', [$filters['from'], $filters['to'].' 23:59:59'])
                    ->where('status', AdmissionStatus::Discharged->value)
                    ->count(),
                'total_biaya' => $this->rupiah((float) AdmissionCharge::query()
                    ->whereIn('admission_id', $this->admissionQuery($filters)->select('id'))
                    ->sum('subtotal')),
            ],
            default => [
                'total_data' => count($rows),
            ],
        };
    }

    /**
     * Data deret untuk chart pada halaman laporan.
     *
     * Setiap laporan memakai kolom tanggalnya sendiri agar grafik sesuai isi laporan.
     * Laporan obat (medicines) tidak punya tanggal, sehingga grafiknya dikosongkan.
     *
     * @param  array<string, mixed>  $filters
     * @return array{labels: array<int, string>, values: array<int, int>}
     */
    public function chart(string $report, array $filters): array
    {
        [$query, $dateColumn, $aggregate] = match ($report) {
            'queues' => [
                Queue::query()->when($filters['doctor_id'], fn ($q) => $q->where('queues.doctor_id', $filters['doctor_id'])),
                'queue_date',
                'count(*)',
            ],
            'examinations' => [
                MedicalRecord::query()->when($filters['doctor_id'], fn ($q) => $q->where('medical_records.doctor_id', $filters['doctor_id'])),
                'examined_at',
                'count(*)',
            ],
            'prescriptions' => [
                Prescription::query()->when($filters['doctor_id'], fn ($q) => $q->where('prescriptions.doctor_id', $filters['doctor_id'])),
                'created_at',
                'count(*)',
            ],
            'patients' => [Patient::query(), 'created_at', 'count(*)'],
            'transactions' => [
                MedicineTransaction::query()->when($filters['medicine_id'], fn ($q) => $q->where('medicine_transactions.medicine_id', $filters['medicine_id'])),
                'created_at',
                'count(*)',
            ],
            'invoices' => [$this->invoiceQuery($filters), 'created_at', 'count(*)'],
            'payments' => [$this->paymentQuery($filters), 'paid_at', 'sum(amount)'],
            'admissions' => [
                Admission::query()->when($filters['doctor_id'], fn ($q) => $q->where('admissions.doctor_id', $filters['doctor_id'])),
                'admitted_at',
                'count(*)',
            ],
            default => [null, null, null],
        };

        if ($query === null) {
            return ['labels' => [], 'values' => []];
        }

        $isDateOnly = $dateColumn === 'queue_date';

        $query->whereBetween(
            $dateColumn,
            $isDateOnly
                ? [$filters['from'], $filters['to']]
                : [$filters['from'], $filters['to'].' 23:59:59'],
        );

        $driver = DB::connection()->getDriverName();

        $expression = $driver === 'sqlite'
            ? "strftime('%Y-%m-%d', {$dateColumn})"
            : "DATE_FORMAT({$dateColumn}, '%Y-%m-%d')";

        $rows = $query->selectRaw("{$expression} as d, {$aggregate} as total")
            ->groupBy('d')
            ->orderBy('d')
            ->pluck('total', 'd');

        return [
            'labels' => $rows->keys()->map(fn ($d) => Carbon::parse($d)->format('d/m'))->all(),
            'values' => $rows->map(fn ($v) => (int) round((float) $v))->values()->all(),
        ];
    }

    /**
     * Deret pendapatan harian berdasarkan pembayaran yang diterima.
     *
     * @param  array<string, mixed>  $filters
     * @return array{labels: array<int, string>, values: array<int, int|float>}
     */
    public function revenueSeries(array $filters): array
    {
        $from = Carbon::parse($filters['from'])->startOfDay();
        $to = Carbon::parse($filters['to'])->startOfDay();
        $days = max(1, $from->diffInDays($to));

        $expression = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m-%d', paid_at)"
            : "DATE_FORMAT(paid_at, '%Y-%m-%d')";

        $rows = Payment::query()
            ->whereBetween('paid_at', [$from, $to->copy()->endOfDay()])
            ->when($filters['method'] ?? null, fn ($q) => $q->where('method', $filters['method']))
            ->selectRaw("{$expression} as d, sum(amount) as total")
            ->groupBy('d')
            ->pluck('total', 'd');

        $labels = [];
        $values = [];

        for ($i = 0; $i <= $days && $i < 120; $i++) {
            $date = $from->copy()->addDays($i);
            $labels[] = $date->format('d/m');
            $values[] = (float) ($rows[$date->toDateString()] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Deret harian untuk dashboard admin.
     *
     * @param  array<string, mixed>  $filters
     * @return array{labels: array<int, string>, values: array<int, int>}
     */
    public function dailySeries(array $filters, string $type): array
    {
        $from = now()->parse($filters['from'])->startOfDay();
        $to = now()->parse($filters['to'])->startOfDay();
        $days = max(1, $from->diffInDays($to));

        $query = match ($type) {
            'queues' => Queue::query()->whereBetween('queue_date', [$from->toDateString(), $to->toDateString()]),
            default => Patient::query()->whereBetween('created_at', [$from, $to->copy()->endOfDay()]),
        };

        if (($filters['doctor_id'] ?? null) && $type === 'queues') {
            $query->where('doctor_id', $filters['doctor_id']);
        }

        $expression = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m-%d', ".($type === 'queues' ? 'queue_date' : 'created_at').')'
            : 'DATE_FORMAT('.($type === 'queues' ? 'queue_date' : 'created_at').", '%Y-%m-%d')";

        $rows = $query->selectRaw("{$expression} as d, count(*) as total")->groupBy('d')->pluck('total', 'd');

        $labels = [];
        $values = [];

        for ($i = 0; $i <= $days && $i < 120; $i++) {
            $date = $from->copy()->addDays($i);
            $labels[] = $date->format('d/m');
            $values[] = (int) ($rows[$date->toDateString()] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /** @return Collection<int, Doctor> */
    public function doctors(): Collection
    {
        return Doctor::query()->orderBy('name')->get();
    }
}
