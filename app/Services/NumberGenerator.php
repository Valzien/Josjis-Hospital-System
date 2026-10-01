<?php

namespace App\Services;

use App\Models\DocumentCounter;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Pembuat nomor dokumen berurutan (rekam medis, resep, dan nomor pasien).
 * Nomor menggunakan pola: PREFIX-YYYYMM-0001
 *
 * Penomoran memakai tabel document_counters, bukan "SELECT maks ... FOR UPDATE"
 * dari tabel dokumen. Alasannya: penguncian baris hanya berguna kalau baris yang
 * dikunci benar-benar ada. Pengujian pada MySQL menunjukkan bahwa
 * "SELECT ... LIKE 'RM-202610-%' ... FOR UPDATE" tidak mengunci apa pun saat
 * periode masih kosong, dan bahkan saat sudah terisi tetap memicu deadlock
 * (1213) ketika beberapa permintaan meminta nomor bersamaan.
 */
class NumberGenerator
{
    public function medicalRecordNumber(): string
    {
        return $this->next('RM');
    }

    public function prescriptionCode(): string
    {
        return $this->next('RS');
    }

    public function patientNumber(string $prefix = 'P'): string
    {
        return $this->next($prefix);
    }

    public function patientNumberPreview(): string
    {
        return $this->peek('P');
    }

    public function medicalRecordNumberPreview(): string
    {
        return $this->peek('RM');
    }

    public function prescriptionPreview(): string
    {
        return $this->peek('RS');
    }

    /** Nomor berikutnya tanpa menyimpan, untuk preview di form. */
    public function peek(string $prefix): string
    {
        $period = $this->period();

        $last = (int) DocumentCounter::query()->forPeriod($prefix, $period)->value('last_value');

        return $this->format($prefix, $period, $last + 1);
    }

    private function next(string $prefix): string
    {
        $period = $this->period();

        $sequence = DB::transaction(function () use ($prefix, $period): int {
            $this->incrementCounter($prefix, $period);

            return (int) DocumentCounter::query()
                ->forPeriod($prefix, $period)
                ->value('last_value');
        });

        return $this->format($prefix, $period, $sequence);
    }

    /**
     * Menaikkan penghitung dalam satu statement.
     *
     * Pada MySQL statement ini sekaligus membuat baris bila periode ini belum
     * punya penghitung, sehingga tidak ada pola "cek lalu tulis" yang bisa
     * balapan. Bentuk satu statement juga menghindari deadlock upgrade kunci:
     * bila dilakukan "INSERT IGNORE" lalu "SELECT ... FOR UPDATE", S-lock dari
     * INSERT IGNORE harus di-upgrade menjadi X-lock, dan beberapa permintaan
     * bersamaan akan saling memblokir sampai deadlock.
     */
    private function incrementCounter(string $prefix, string $period): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            // SQLite hanya dipakai untuk pengembangan dan pengujian dengan
            // satu koneksi, jadi tidak ada balapan yang perlu ditangani.
            $counter = DocumentCounter::query()->forPeriod($prefix, $period)->first();

            if ($counter === null) {
                DocumentCounter::query()->create([
                    'scope' => $prefix,
                    'period' => $period,
                    'last_value' => 1,
                ]);

                return;
            }

            $counter->increment('last_value');

            return;
        }

        DB::update(
            'INSERT INTO document_counters (`scope`, `period`, `last_value`, `created_at`, `updated_at`)
             VALUES (?, ?, 1, NOW(), NOW())
             ON DUPLICATE KEY UPDATE `last_value` = `last_value` + 1, `updated_at` = NOW()',
            [$prefix, $period]
        );
    }

    private function period(): string
    {
        return now()->format('Ym');
    }

    private function format(string $prefix, string $period, int $sequence): string
    {
        if ($sequence > 9999) {
            throw new RuntimeException("Kuota nomor {$prefix} bulan ini sudah habis.");
        }

        return sprintf('%s-%s-%04d', $prefix, $period, $sequence);
    }
}
