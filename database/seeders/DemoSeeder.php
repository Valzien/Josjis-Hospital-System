<?php

namespace Database\Seeders;

use App\Enums\ActiveStatus;
use App\Enums\Day;
use App\Enums\PrescriptionStatus;
use App\Enums\QueueStatus;
use App\Enums\TransactionType;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\MedicalRecord;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Pharmacist;
use App\Models\Prescription;
use App\Models\PrescriptionDetail;
use App\Models\Queue;
use App\Models\Receptionist;
use App\Models\User;
use App\Services\NumberGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder data demo JOSJIS Hospital System.
 *
 * Menghasilkan akun demo sesuai config('jhs.demo_accounts'), master data,
 * jadwal dokter, dan riwayat pelayanan agar seluruh dashboard terisi.
 */
class DemoSeeder extends Seeder
{
    private NumberGenerator $numbers;

    public function run(): void
    {
        $this->numbers = app(NumberGenerator::class);

        DB::transaction(function (): void {
            $this->seedStaff();
            $this->seedDoctors();
            $this->seedSchedules();
            $this->seedMedicines();
            $this->seedPatients();
        });

        $this->seedServiceHistory();
    }

    /* ------------------------------------------------------------- Akun */

    private function seedStaff(): void
    {
        $password = Hash::make('password');

        $this->upsertUser('Administrator JOSJIS', 'admin@josjis.test', 'admin', $password);

        $receptionUser = $this->upsertUser('Rina Puspita', 'resepsionis@josjis.test', 'receptionist', $password);
        $this->upsertUser('Bagus Wicaksono', 'apoteker@josjis.test', 'pharmacist', $password);

        Receptionist::updateOrCreate(
            ['user_id' => $receptionUser->id],
            ['name' => 'Rina Puspita', 'phone' => '081200000001', 'shift' => 'Pagi', 'status' => ActiveStatus::Active->value],
        );

        Pharmacist::updateOrCreate(
            ['user_id' => User::query()->where('email', 'apoteker@josjis.test')->value('id')],
            ['name' => 'Bagus Wicaksono', 'phone' => '081200000002', 'license_number' => 'AP-00001', 'status' => ActiveStatus::Active->value],
        );
    }

    private function upsertUser(string $name, string $email, string $role, string $password): User
    {
        return User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password,
                'email_verified_at' => now(),
                'role' => $role,
                'status' => ActiveStatus::Active->value,
                'phone' => '081200000000',
            ],
        );
    }

    /* ------------------------------------------------------------ Dokter */

    private function seedDoctors(): void
    {
        $password = Hash::make('password');

        $doctors = [
            ['email' => 'dokter@josjis.test', 'name' => 'dr. Andi Prasetyo', 'code' => 'DR-001', 'specialization' => 'Penyakit Dalam', 'phone' => '081200000010', 'bio' => 'Spesialis penyakit dalam untuk pasien dewasa.'],
            ['email' => 'dokter.anak@josjis.test', 'name' => 'dr. Sari Dewi', 'code' => 'DR-002', 'specialization' => 'Penyakit Anak', 'phone' => '081200000011', 'bio' => 'Spesialis anak untuk tumbuh kembang.'],
            ['email' => 'dokter.kandungan@josjis.test', 'name' => 'dr. Hendra Gunawan', 'code' => 'DR-003', 'specialization' => 'Kandungan', 'phone' => '081200000012', 'bio' => 'Spesialis kandungan dan kebidanan.'],
            ['email' => 'dokter.saraf@josjis.test', 'name' => 'dr. Maya Lutfiani', 'code' => 'DR-004', 'specialization' => 'Saraf', 'phone' => '081200000013', 'bio' => 'Spesialis saraf dan sakit kepala.'],
        ];

        foreach ($doctors as $data) {
            $user = $this->upsertUser($data['name'], $data['email'], 'doctor', $password);

            Doctor::updateOrCreate(
                ['doctor_code' => $data['code']],
                [
                    'user_id' => $user->id,
                    'name' => $data['name'],
                    'specialization' => $data['specialization'],
                    'phone' => $data['phone'],
                    'email' => $data['email'],
                    'bio' => $data['bio'],
                    'status' => ActiveStatus::Active->value,
                ],
            );
        }
    }

    private function seedSchedules(): void
    {
        $days = [Day::Monday, Day::Tuesday, Day::Wednesday, Day::Thursday, Day::Friday, Day::Saturday];

        foreach (Doctor::query()->get() as $index => $doctor) {
            foreach ($days as $dayIndex => $day) {
                $morning = $index % 2 === 0;

                DoctorSchedule::updateOrCreate(
                    [
                        'doctor_id' => $doctor->id,
                        'day' => $day->value,
                        'start_time' => $morning ? '08:00:00' : '13:00:00',
                    ],
                    [
                        'end_time' => $morning ? '12:00:00' : '17:00:00',
                        'room' => 'Ruang '.($dayIndex + 1),
                        'quota' => 30,
                        'status' => ActiveStatus::Active->value,
                    ],
                );
            }
        }
    }

    /* ------------------------------------------------------------- Obat */

    private function seedMedicines(): void
    {
        $medicines = [
            ['AMD-001', 'Amoksisilin 500 mg', 'Antibiotik', 'Kapsul', 240, 30, 12500, 'Antibiotik untuk infeksi bakteri.'],
            ['AMD-002', 'Amoxicillin Sirup 250 mg', 'Antibiotik', 'Botol', 45, 15, 38000, 'Sirup antibiotik untuk anak.'],
            ['PST-001', 'Paracetamol 500 mg', 'Analgetik', 'Tablet', 400, 50, 3500, 'Penurun panas dan pereda nyeri.'],
            ['PST-002', 'Paracetamol 650 mg', 'Analgetik', 'Tablet', 260, 40, 5200, 'Dosis tinggi untuk dewasa.'],
            ['IBP-001', 'Ibuprofen 400 mg', 'Analgetik', 'Tablet', 180, 25, 6800, 'Anti radang untuk nyeri ringan.'],
            ['MET-001', 'Metronidazol 500 mg', 'Antibiotik', 'Tablet', 210, 30, 9500, 'Antibiotik untuk infeksi parasit.'],
            ['CET-001', 'Cetirizine 10 mg', 'Antihistamin', 'Tablet', 300, 40, 8900, 'Obat untuk gejala alergi.'],
            ['CET-002', 'Cetirizine Sirup 60 ml', 'Antihistamin', 'Botol', 38, 15, 27000, 'Sirup antihistamin untuk anak.'],
            ['OME-001', 'Omeprazol 20 mg', 'Antasid', 'Kapsul', 220, 30, 14500, 'Obat maag untuk menurunkan asam gastrik.'],
            ['SAL-001', 'Salbutamol Inhaler', 'Inhaler', 'Inhaler', 24, 10, 95000, 'Obat untuk sesak napas.'],
            ['DEX-001', 'Dexamethasone 0,5 mg', 'Kortikosteroid', 'Tablet', 150, 20, 11000, 'Kortikosteroid untuk mereda inflamasi.'],
            ['VIT-001', 'Vitamin C 500 mg', 'Vitamin', 'Tablet', 500, 60, 4500, 'Vitamin C untuk ketahanan tubuh.'],
            ['VIT-002', 'Vitamin B Complex', 'Vitamin', 'Tablet', 280, 40, 12500, 'Kombinasi vitamin B.'],
            ['ORS-001', 'ORS Sachet', 'Vitamin', 'Sachet', 160, 25, 4500, 'Pen pengganti cairan saat diare.'],
            ['BET-001', 'Betadine 100 ml', 'Antiseptik', 'Botol', 55, 15, 18500, 'Antiseptik untuk luka.'],
            ['INS-001', 'Insulin Glargine 100 IU/ml', 'Insulin', 'Vial', 12, 5, 285000, 'Insulin untuk diabetes mellitus.'],
        ];

        foreach ($medicines as [$code, $name, $category, $unit, $stock, $min, $price, $description]) {
            Medicine::updateOrCreate(
                ['medicine_code' => $code],
                [
                    'name' => $name,
                    'category' => $category,
                    'unit' => $unit,
                    'stock' => $stock,
                    'minimum_stock' => $min,
                    'price' => $price,
                    'description' => $description,
                    'status' => ActiveStatus::Active->value,
                ],
            );
        }

        // Beberapa obat dibuat stok rendah agar peringatan terlihat di dashboard.
        Medicine::whereIn('medicine_code', ['CET-002', 'INS-001'])->update(['stock' => 6]);
    }

    /* ----------------------------------------------------------- Pasien */

    private function seedPatients(): void
    {
        $password = Hash::make('password');

        $patients = [
            ['email' => 'pasien@josjis.test', 'name' => 'Dewi Lestari', 'gender' => 'P', 'birth_date' => '1995-04-12', 'nik' => '3273014504120001', 'blood_type' => 'O', 'phone' => '081300000001', 'address' => 'Jl. Melati No. 21, Bandung'],
            ['email' => 'budi@josjis.test', 'name' => 'Budi Santoso', 'gender' => 'L', 'birth_date' => '1988-09-30', 'nik' => '3273010909880002', 'blood_type' => 'A', 'phone' => '081300000002', 'address' => 'Jl. Kenanga No. 8, Bandung'],
            ['email' => 'citra@josjis.test', 'name' => 'Citra Ayu', 'gender' => 'P', 'birth_date' => '2001-02-18', 'nik' => '3273015502980003', 'blood_type' => 'B', 'phone' => '081300000003', 'address' => 'Jl. Mawar No. 45, Bandung'],
            ['email' => 'rizky@josjis.test', 'name' => 'Rizky Ramadhan', 'gender' => 'L', 'birth_date' => '1992-11-05', 'nik' => '3273010511920004', 'blood_type' => 'AB', 'phone' => '081300000004', 'address' => 'Jl. Anggrek No. 12, Bandung'],
        ];

        foreach ($patients as $data) {
            $user = $this->upsertUser($data['name'], $data['email'], 'patient', $password);

            Patient::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'medical_record_number' => $this->numbers->patientNumber(),
                    'nik' => $data['nik'],
                    'name' => $data['name'],
                    'gender' => $data['gender'],
                    'birth_date' => $data['birth_date'],
                    'phone' => $data['phone'],
                    'address' => $data['address'],
                    'blood_type' => $data['blood_type'],
                    'emergency_contact_name' => 'Keluarga',
                    'emergency_contact_phone' => '081300000099',
                ],
            );
        }

        // Pasien tanpa akun, dibuat langsung oleh resepsionis.
        $guests = [
            ['Agus Setiawan', 'L', '1979-06-21', '3273012106790005', 'O-', '081300000010', 'Jl. Flamboyan No. 3, Cimahi'],
            ['Nur Hasanah', 'P', '1996-01-09', '3273014901960006', 'B-', '081300000011', 'Jl. Cendana No. 17, Bandung'],
        ];

        foreach ($guests as [$name, $gender, $birthDate, $nik, $bloodType, $phone, $address]) {
            Patient::updateOrCreate(
                ['nik' => $nik],
                [
                    'user_id' => null,
                    'medical_record_number' => $this->numbers->patientNumber(),
                    'name' => $name,
                    'gender' => $gender,
                    'birth_date' => $birthDate,
                    'phone' => $phone,
                    'address' => $address,
                    'blood_type' => $bloodType,
                    'emergency_contact_name' => 'Keluarga',
                    'emergency_contact_phone' => '081300000098',
                ],
            );
        }
    }

    /* ------------------------------------------------- Riwayat layanan */

    private function seedServiceHistory(): void
    {
        if (Queue::query()->exists()) {
            return;
        }

        $doctors = Doctor::query()->orderBy('id')->get();
        $patients = Patient::query()->orderBy('id')->get();
        $medicines = Medicine::query()->orderBy('id')->get();
        $receptionUser = User::query()->where('role', 'receptionist')->first();
        $pharmacistUser = User::query()->where('role', 'pharmacist')->first();

        if ($doctors->isEmpty() || $patients->isEmpty() || $medicines->isEmpty()) {
            return;
        }

        // Enam encounter pada hari-hari sebelumnya agar grafik laporan terisi.
        for ($day = 6; $day >= 1; $day--) {
            $date = now()->subDays($day);
            $dateString = $date->toDateString();

            foreach ($doctors->values() as $doctorIndex => $doctor) {
                $perDoctor = 2 + ($doctorIndex % 2);

                for ($i = 1; $i <= $perDoctor; $i++) {
                    $patient = $patients->random();
                    $number = sprintf('A-%03d', ($doctorIndex * 10) + $i);

                    if (Queue::query()
                        ->where('doctor_id', $doctor->id)
                        ->whereDate('queue_date', $dateString)
                        ->where('queue_number', $number)
                        ->exists()) {
                        continue;
                    }

                    $calledAt = $date->copy()->setTime(9, $i * 5);

                    $queue = Queue::create([
                        'patient_id' => $patient->id,
                        'doctor_id' => $doctor->id,
                        'doctor_schedule_id' => $doctor->schedules()->where('day', $date->dayOfWeek)->value('id'),
                        'queue_number' => $number,
                        'queue_date' => $dateString,
                        'status' => QueueStatus::Completed->value,
                        'called_at' => $calledAt,
                        'started_at' => $calledAt->copy()->addMinutes(3),
                        'completed_at' => $calledAt->copy()->addMinutes(12),
                        'complaint_note' => 'Keluhan sejak dua hari.',
                        'created_by' => $receptionUser?->id,
                    ]);

                    $record = MedicalRecord::create([
                        'record_number' => $this->numbers->medicalRecordNumber(),
                        'patient_id' => $patient->id,
                        'doctor_id' => $doctor->id,
                        'queue_id' => $queue->id,
                        'complaint' => 'Keluhan yang dibawa pasien.',
                        'examination_result' => 'Kondisi umum baik.',
                        'diagnosis' => 'Diagnosis sementara',
                        'treatment' => 'Perawatan dan kontrol ulang.',
                        'temperature' => 37.2,
                        'blood_pressure' => 120.0,
                        'weight' => 65,
                        'height' => 170.0,
                        'examined_at' => $queue->completed_at,
                    ]);

                    if ($i % 2 === 0) {
                        $this->attachPrescription($record, $doctor, $medicines, $pharmacistUser, PrescriptionStatus::Completed);
                    } elseif ($i % 3 === 0) {
                        $this->attachPrescription($record, $doctor, $medicines, $pharmacistUser, PrescriptionStatus::Ready);
                    } elseif ($i % 3 === 1) {
                        $this->attachPrescription($record, $doctor, $medicines, $pharmacistUser, PrescriptionStatus::Pending);
                    }
                }
            }
        }

        // Antrean aktif hari ini agar dashboard tidak kosong.
        foreach ($doctors->take(2) as $doctorIndex => $doctor) {
            $schedule = $doctor->schedules()->where('day', now()->dayOfWeek)->first();

            if (! $schedule) {
                continue;
            }

            for ($i = 1; $i <= 3; $i++) {
                $patient = $patients->random();
                $number = sprintf('A-%03d', ($doctorIndex * 10) + $i);

                if (Queue::query()
                    ->where('doctor_id', $doctor->id)
                    ->whereDate('queue_date', now()->toDateString())
                    ->where('queue_number', $number)
                    ->exists()) {
                    continue;
                }

                $status = match ($i) {
                    1 => QueueStatus::InExamination,
                    2 => QueueStatus::Called,
                    default => QueueStatus::Waiting,
                };

                Queue::create([
                    'patient_id' => $patient->id,
                    'doctor_id' => $doctor->id,
                    'doctor_schedule_id' => $schedule->id,
                    'queue_number' => $number,
                    'queue_date' => now()->toDateString(),
                    'status' => $status->value,
                    'called_at' => $status === QueueStatus::Waiting ? null : now()->subMinutes(10 * $i),
                    'started_at' => $status === QueueStatus::InExamination ? now()->subMinutes(5) : null,
                    'completed_at' => null,
                    'complaint_note' => null,
                    'created_by' => $receptionUser?->id,
                ]);
            }
        }

        // Satu antrean menunggu tanpa rekam medis, siap diperiksa dokter.
        $waitingQueue = Queue::query()
            ->whereDate('queue_date', now()->toDateString())
            ->where('status', QueueStatus::Waiting->value)
            ->orderBy('id')
            ->first();

        if ($waitingQueue) {
            MedicalRecord::create([
                'record_number' => $this->numbers->medicalRecordNumber(),
                'patient_id' => $waitingQueue->patient_id,
                'doctor_id' => $waitingQueue->doctor_id,
                'queue_id' => $waitingQueue->id,
                'complaint' => '',
                'examined_at' => null,
            ]);
        }
    }

    private function attachPrescription(MedicalRecord $record, Doctor $doctor, $medicines, ?User $pharmacist, PrescriptionStatus $status): void
    {
        $prescription = Prescription::create([
            'code' => $this->numbers->prescriptionCode(),
            'medical_record_id' => $record->id,
            'patient_id' => $record->patient_id,
            'doctor_id' => $doctor->id,
            'status' => PrescriptionStatus::Pending->value,
            'notes' => 'Diminum sesuai anjuran dokter.',
            'total_price' => 0,
        ]);

        $details = $medicines->random(min(3, max(1, $medicines->count())));
        $total = 0;

        foreach ($details as $medicine) {
            $quantity = random_int(1, 3);
            $subtotal = (float) $medicine->price * $quantity;

            PrescriptionDetail::create([
                'prescription_id' => $prescription->id,
                'medicine_id' => $medicine->id,
                'quantity' => $quantity,
                'dosage' => '500 mg',
                'instructions' => 'Diminum tiga kali sehari setelah makan.',
                'price' => $medicine->price,
                'subtotal' => $subtotal,
            ]);

            $total += $subtotal;

            if (in_array($status, [PrescriptionStatus::Ready, PrescriptionStatus::Completed], true)) {
                $stockBefore = (int) $medicine->stock;
                $stockAfter = max(0, $stockBefore - $quantity);

                $medicine->update(['stock' => $stockAfter]);

                $medicine->transactions()->create([
                    'type' => TransactionType::Out->value,
                    'quantity' => -$quantity,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'reference_type' => 'prescription',
                    'reference_id' => $prescription->id,
                    'notes' => 'Penyerahan resep '.$prescription->code.'.',
                    'user_id' => $pharmacist?->id,
                ]);
            }
        }

        $prescription->update([
            'total_price' => $total,
            'status' => $status->value,
            'processed_at' => $status === PrescriptionStatus::Pending ? null : now()->subHours(random_int(1, 20)),
            'processed_by' => $status === PrescriptionStatus::Pending ? null : $pharmacist?->id,
        ]);
    }
}
