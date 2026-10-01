<?php

namespace App\Services;

use App\Enums\QueueStatus;
use App\Enums\TransactionType;
use App\Models\Doctor;
use App\Models\MedicalRecord;
use App\Models\Medicine;
use App\Models\MedicineTransaction;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Queue;
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
        [$query, $dateColumn] = match ($report) {
            'queues' => [
                Queue::query()->when($filters['doctor_id'], fn ($q) => $q->where('queues.doctor_id', $filters['doctor_id'])),
                'queue_date',
            ],
            'examinations' => [
                MedicalRecord::query()->when($filters['doctor_id'], fn ($q) => $q->where('medical_records.doctor_id', $filters['doctor_id'])),
                'examined_at',
            ],
            'prescriptions' => [
                Prescription::query()->when($filters['doctor_id'], fn ($q) => $q->where('prescriptions.doctor_id', $filters['doctor_id'])),
                'created_at',
            ],
            'patients' => [Patient::query(), 'created_at'],
            'transactions' => [
                MedicineTransaction::query()->when($filters['medicine_id'], fn ($q) => $q->where('medicine_transactions.medicine_id', $filters['medicine_id'])),
                'created_at',
            ],
            default => [null, null],
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

        $rows = $query->selectRaw("{$expression} as d, count(*) as total")
            ->groupBy('d')
            ->orderBy('d')
            ->pluck('total', 'd');

        return [
            'labels' => $rows->keys()->map(fn ($d) => Carbon::parse($d)->format('d/m'))->all(),
            'values' => $rows->map(fn ($v) => (int) $v)->values()->all(),
        ];
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
