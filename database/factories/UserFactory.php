<?php

namespace Database\Factories;

use App\Enums\ActiveStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => UserRole::Patient->value,
            'status' => ActiveStatus::Active->value,
            'phone' => fake()->numerify('08##########'),
            'last_login_at' => null,
            'remember_token' => Str::random(10),
        ];
    }

    public function role(UserRole|string $role): static
    {
        $value = $role instanceof UserRole ? $role->value : $role;

        return $this->state(fn () => ['role' => $value]);
    }

    public function admin(): static
    {
        return $this->role(UserRole::Admin);
    }

    public function receptionist(): static
    {
        return $this->role(UserRole::Receptionist);
    }

    public function doctor(): static
    {
        return $this->role(UserRole::Doctor);
    }

    public function pharmacist(): static
    {
        return $this->role(UserRole::Pharmacist);
    }

    public function patient(): static
    {
        return $this->role(UserRole::Patient);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => ActiveStatus::Inactive->value]);
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }
}
