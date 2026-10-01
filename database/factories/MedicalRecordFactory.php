<?php

namespace Database\Factories;

use App\Models\Doctor;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\Queue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MedicalRecord>
 */
class MedicalRecordFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'record_number' => $this->faker->unique()->numerify('RM-########'),
            'patient_id' => Patient::factory(),
            'doctor_id' => Doctor::factory(),
            'queue_id' => null,
            'complaint' => fake()->randomElement([
                'Demam dan sakit kepala sejak dua hari.',
                'Batuk kering lebih dari satu minggu.',
                'Nyeri perut setelah makan.',
                'Kelelahan berkepanjangan.',
                'Ruam pada lengan dan kaki.',
                'Sulit tidur dan sering pusing.',
            ]),
            'examination_result' => fake()->randomElement([
                'Temperatur 38,5 derajat C, pernapasan normal, mukosa tidak pucat.',
                'Tekanan darah 130/85 mmHg, denyut nadi 88 kali per menit.',
                'Tidak ditemukan kelainan fisik yang signifikan.',
                'Terdapat kemerahan pada area kulit lengan atas.',
            ]),
            'diagnosis' => fake()->randomElement([
                'Demam typhoid',
                'Infeksi saluran napas atas',
                'Gastritis',
                'Hipertensi tahap 1',
                'Dermatitis kontak',
                'Insomnia',
            ]),
            'treatment' => fake()->randomElement([
                'Istirahat, perbanyak cairan, dan kontrol ulang dalam tiga hari.',
                'Antibiotik sesuai dosis dan kontrol ulang setelah tiga hari.',
                'Penggunaan obat secara berkala sesuai anjuran dokter.',
                'Atur pola tidur, kelola stres, dan terapi.',
                'Rawat jalan dengan obat penghilang rasa sakit.',
            ]),
            'notes' => fake()->optional()->sentence(10),
            'temperature' => fake()->randomFloat(1, 36.0, 39.5),
            'blood_pressure' => fake()->randomFloat(1, 110.0, 150.0),
            'weight' => fake()->numberBetween(45, 95),
            'height' => fake()->randomFloat(1, 150.0, 185.0),
            'examined_at' => now()->subHours(fake()->numberBetween(1, 72)),
        ];
    }

    public function forQueue(Queue $queue, ?Doctor $doctor = null): static
    {
        return $this->state(fn () => [
            'queue_id' => $queue->id,
            'patient_id' => $queue->patient_id,
            'doctor_id' => $doctor?->id ?? $queue->doctor_id,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn () => ['examined_at' => null]);
    }
}
