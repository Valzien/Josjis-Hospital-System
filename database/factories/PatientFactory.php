<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $gender = fake()->randomElement(Gender::cases());

        return [
            'user_id' => null,
            'medical_record_number' => $this->faker->unique()->numerify('RM-########'),
            'nik' => $this->faker->unique()->numerify('##############'),
            'name' => fake()->name(),
            'gender' => $gender->value,
            'birth_date' => fake()->dateTimeBetween('-70 years', '-1 year')->format('Y-m-d'),
            'phone' => fake()->numerify('08##########'),
            'address' => fake()->address(),
            'allergies' => null,
            'blood_type' => fake()->randomElement(['A', 'B', 'AB', 'O', 'A-', 'B-', 'AB-', 'O-']),
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_phone' => fake()->numerify('08##########'),
        ];
    }

    /** Pasien dengan akun pengguna berstatus patient. */
    public function withAccount(?string $email = null): static
    {
        return $this->state(function (array $attributes) use ($email) {
            $user = User::factory()->create([
                'name' => $attributes['name'],
                'email' => $email ?? Str::lower(Str::random(6)).'@example.test',
            ]);

            return ['user_id' => $user->id];
        });
    }

    /** Profil belum dilengkapi — tidak boleh bisa mengambil antrean. */
    public function incompleteProfile(): static
    {
        return $this->state(fn () => [
            'gender' => null,
            'birth_date' => null,
            'phone' => null,
            'address' => null,
            'blood_type' => null,
            'emergency_contact_name' => null,
            'emergency_contact_phone' => null,
        ]);
    }

    public function withAllergies(string $allergies = 'Penicillin'): static
    {
        return $this->state(fn () => ['allergies' => $allergies]);
    }
}
