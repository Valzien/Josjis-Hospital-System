<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\DocumentCounter;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Services\NumberGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Membuktikan bahwa penguncian baris di NumberGenerator benar-benar bekerja.
 *
 * Uji ini harus berjalan di MySQL. SQLite mengabaikan lockForUpdate(), sehingga
 * di sana pengujian ini dilewati.
 *
 * Catatan: kelas ini sengaja tidak memakai RefreshDatabase. Worker berjalan di
 * proses PHP terpisah dengan koneksi sendiri, sehingga data yang dibuat di sini
 * harus benar-benar di-commit agar terlihat oleh mereka. Semua baris yang dibuat
 * dibersihkan di tearDown().
 */
class NumberGeneratorConcurrencyTest extends TestCase
{
    private const WORKERS = 6;

    private string $scratch;

    /** False bila pengujian dilewati karena bukan MySQL. */
    private bool $usesMysql = false;

    /** @var array<int, int> */
    private array $doctors = [];

    /** @var array<int, int> */
    private array $patients = [];

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('lockForUpdate() hanya teruji pada MySQL.');
        }

        $this->usesMysql = true;
        $this->scratch = storage_path('framework/testing/concurrency-'.getmypid());

        $this->purgeProbes();
        DocumentCounter::query()->delete();

        File::ensureDirectoryExists($this->scratch);
    }

    protected function tearDown(): void
    {
        if ($this->usesMysql) {
            Doctor::query()->whereIn('id', $this->doctors)->forceDelete();
            Patient::query()->withTrashed()->whereIn('id', $this->patients)->forceDelete();

            $this->purgeProbes();

            File::deleteDirectory($this->scratch);
        }

        parent::tearDown();
    }

    /** Menghapus seluruh jejak probe, termasuk sisa run sebelumnya. */
    private function purgeProbes(): void
    {
        MedicalRecord::query()->where('complaint', 'concurrency probe')->forceDelete();
        MedicalRecord::query()->where('complaint', 'period anchor')->forceDelete();
        MedicalRecord::query()->where('complaint', 'preview probe')->forceDelete();
        Patient::query()->withTrashed()->where('medical_record_number', 'like', 'ZC-%')->forceDelete();
    }

    public function test_medical_record_numbers_are_unique_under_concurrent_requests(): void
    {
        $doctor = $this->doctor();
        $patient = $this->patient();

        $outcomes = $this->runWorkers('record', array_fill(0, self::WORKERS, [$patient->id, $doctor->id]));
        $numbers = $this->assertAllSucceeded($outcomes);

        $this->assertSame($numbers, array_values(array_unique($numbers)), 'Nomor rekam medis bentrok.');
        $this->assertSame($this->expectedSequence('RM', 1), $this->sorted($numbers));
    }

    public function test_medical_record_numbers_continue_after_an_existing_document(): void
    {
        $doctor = $this->doctor();
        $patient = $this->patient();

        // Dokumen pertama dibuat lewat generator supaya penghitung ikutsinkron.
        MedicalRecord::create([
            'record_number' => app(NumberGenerator::class)->medicalRecordNumber(),
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'complaint' => 'period anchor',
        ]);

        $outcomes = $this->runWorkers('record', array_fill(0, self::WORKERS, [$patient->id, $doctor->id]));
        $numbers = $this->assertAllSucceeded($outcomes);

        $this->assertSame($numbers, array_values(array_unique($numbers)), 'Nomor rekam medis bentrok.');
        $this->assertSame($this->expectedSequence('RM', 2), $this->sorted($numbers));
    }

    public function test_patient_numbers_are_unique_under_concurrent_requests(): void
    {
        $niks = array_map(
            fn (int $offset): string => sprintf('%016d', 90_000_000_000_000 + $offset),
            range(0, self::WORKERS - 1)
        );

        $outcomes = $this->runWorkers('patient', array_map(fn (string $nik): array => [$nik], $niks));
        $numbers = $this->assertAllSucceeded($outcomes);

        $this->assertSame($numbers, array_values(array_unique($numbers)), 'Nomor pasien bentrok.');
        $this->assertSame($this->expectedSequence('ZC', 1), $this->sorted($numbers));
    }

    /**
     * @return array<int, string>
     */
    private function expectedSequence(string $prefix, int $start): array
    {
        return array_map(
            fn (int $offset): string => sprintf('%s-%s-%04d', $prefix, now()->format('Ym'), $start + $offset),
            range(0, self::WORKERS - 1)
        );
    }

    /**
     * @param  array<int, string>  $numbers
     * @return array<int, string>
     */
    private function sorted(array $numbers): array
    {
        sort($numbers);

        return $numbers;
    }

    private function doctor(): Doctor
    {
        return tap(Doctor::factory()->create(), function (Doctor $doctor): void {
            $this->doctors[] = $doctor->id;
        });
    }

    private function patient(): Patient
    {
        return tap(Patient::factory()->create(), function (Patient $patient): void {
            $this->patients[] = $patient->id;
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $outcomes
     * @return array<int, string>
     */
    private function assertAllSucceeded(array $outcomes): array
    {
        $errors = array_values(array_filter(
            array_column($outcomes, 'error'),
            fn (?string $error): bool => $error !== null && $error !== ''
        ));

        $this->assertSame([], $errors, 'Worker gagal: '.json_encode($outcomes, JSON_PRETTY_PRINT));

        return array_values(array_filter(array_column($outcomes, 'number')));
    }

    /**
     * Menjalankan beberapa proses PHP yang saling berebut nomor dokumen.
     *
     * @param  array<int, array<int, int|string>>  $arguments
     * @return array<int, array<string, mixed>>
     */
    private function runWorkers(string $mode, array $arguments): array
    {
        $barrier = $this->scratch.'/barrier';
        $files = [];
        $processes = [];
        $streams = [];

        foreach ($arguments as $index => $extra) {
            $file = $this->scratch."/{$mode}-{$index}.json";
            $files[] = $file;

            $command = array_merge([
                PHP_BINARY,
                base_path('tests/Fixtures/concurrent_number_worker.php'),
                $mode,
                $barrier,
                $file,
            ], array_map('strval', $extra));

            $pipes = [];
            $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);

            if (! is_resource($process)) {
                $this->fail("Gagal menjalankan worker ke-{$index}.");
            }

            $processes[] = $process;
            $streams[] = $pipes;
        }

        // Beri waktu semua worker menyelesaikan boot sebelum sinyal diberikan.
        usleep(2_500_000);
        touch($barrier);

        foreach ($processes as $index => $process) {
            stream_get_contents($streams[$index][1]);
            stream_get_contents($streams[$index][2]);
            fclose($streams[$index][1]);
            fclose($streams[$index][2]);
            proc_close($process);
        }

        return array_map(function (string $file): array {
            if (! is_file($file)) {
                return ['number' => null, 'error' => 'worker tidak menulis hasil'];
            }

            return json_decode((string) file_get_contents($file), true) ?? ['number' => null, 'error' => 'hasil tidak valid'];
        }, $files);
    }

    public function test_preview_does_not_reserve_a_number(): void
    {
        $generator = app(NumberGenerator::class);

        $preview = $generator->medicalRecordNumberPreview();
        $doctor = $this->doctor();
        $patient = $this->patient();

        MedicalRecord::create([
            'record_number' => $preview,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'complaint' => 'preview probe',
        ]);

        $this->assertSame($preview, $generator->medicalRecordNumberPreview(), 'Preview harus memprediksi nomor berikutnya, bukan menguncinya.');
    }
}
