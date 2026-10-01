<?php

/*
 * Worker yang dijalankan sebagai proses terpisah untuk menguji NumberGenerator
 * di bawah kondisi konkuren. Dipanggil dari NumberGeneratorConcurrencyTest.
 *
 * Pemakaian: php concurrent_number_worker.php <mode> <barrier> <result> [id..]
 *   mode   : record | patient
 *   barrier: file yangIPP muncul sebagai sinyal semua worker boleh mulai
 *   result : file hasil yang ditulis worker
 */

use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Services\NumberGenerator;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../../vendor/autoload.php';

$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$mode = $argv[1];
$barrier = $argv[2];
$result = $argv[3];
$extra = array_slice($argv, 4);

$deadline = microtime(true) + 20;

while (! file_exists($barrier) && microtime(true) < $deadline) {
    usleep(1000);
}

$outcome = ['mode' => $mode, 'pid' => getmypid(), 'number' => null, 'error' => null];

try {
    if ($mode === 'record') {
        [$patientId, $doctorId] = $extra;

        // Pola yang dipakai Doctor\ExaminationController: pembuatan nomor dan
        // penyimpanan baris berada dalam satu transaksi.
        DB::transaction(function () use ($patientId, $doctorId, &$outcome) {
            $number = app(NumberGenerator::class)->medicalRecordNumber();
            $outcome['number'] = $number;

            usleep(random_int(30_000, 150_000));

            MedicalRecord::create([
                'record_number' => $number,
                'patient_id' => $patientId,
                'doctor_id' => $doctorId,
                'complaint' => 'concurrency probe',
            ]);
        });
    } elseif ($mode === 'patient') {
        [$nik] = $extra;

        // Pola yang dipakai PatientController::store saat ini: nomor dibuat di
        // dalam transaksinya sendiri, lalu baris disimpan setelah transaksi itu
        // sudah selesai.
        $number = app(NumberGenerator::class)->patientNumber('ZC');
        $outcome['number'] = $number;

        usleep(random_int(30_000, 150_000));

        Patient::create([
            'medical_record_number' => $number,
            'nik' => $nik,
            'name' => 'Concurrency Probe '.$nik,
        ]);
    } else {
        throw new InvalidArgumentException("Mode tidak dikenal: {$mode}");
    }
} catch (Throwable $e) {
    $outcome['error'] = $e::class.': '.$e->getMessage();
}

file_put_contents($result, json_encode($outcome));
