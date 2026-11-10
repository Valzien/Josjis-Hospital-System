<?php

namespace Database\Seeders;

use App\Enums\ActiveStatus;
use App\Enums\Day;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Pharmacist;
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
    }

    /* ------------------------------------------------------------- Akun */

    private function seedStaff(): void
    {
        $password = Hash::make('password');

        $this->upsertUser('Administrator JOSJIS', 'admin@josjis.test', 'admin', $password);

        $receptionUser = $this->upsertUser('Rina Puspita', 'resepsionis@josjis.test', 'receptionist', $password);
        $this->upsertUser('Bagus Wicaksono', 'apoteker@josjis.test', 'pharmacist', $password);
        $this->upsertUser('Sinta Maharani', 'kasir@josjis.test', 'cashier', $password);

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
        $days = [Day::Sunday, Day::Monday, Day::Tuesday, Day::Wednesday, Day::Thursday, Day::Friday, Day::Saturday];

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

        $user = $this->upsertUser('Dewi Lestari', 'pasien@josjis.test', 'patient', $password);

        Patient::updateOrCreate(
            ['user_id' => $user->id],
            [
                'medical_record_number' => $this->numbers->patientNumber(),
                'nik' => '3273014504120001',
                'name' => 'Dewi Lestari',
                'gender' => 'P',
                'birth_date' => '1995-04-12',
                'phone' => '081300000001',
                'address' => 'Jl. Melati No. 21, Bandung',
                'blood_type' => 'O',
                'emergency_contact_name' => 'Keluarga',
                'emergency_contact_phone' => '081300000099',
            ],
        );
    }
}
