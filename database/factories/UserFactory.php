<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Enums\UserStatus;
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

    public function definition(): array
    {
        $name = fake()->name();

        return [
            'name' => $name,
            'username' => Str::lower(Str::slug(Str::before($name, ' '), '')).fake()->unique()->numberBetween(100, 99999),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => Role::Volunteer,
            'bio' => fake()->optional()->sentence(12),
            'status' => UserStatus::Active,
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(['role' => Role::Admin]);
    }

    public function organizer(): static
    {
        return $this->state(['role' => Role::Organizer]);
    }

    public function blocked(): static
    {
        return $this->state(['status' => UserStatus::Blocked]);
    }

    public function unverified(): static
    {
        return $this->state(['email_verified_at' => null]);
    }
}
