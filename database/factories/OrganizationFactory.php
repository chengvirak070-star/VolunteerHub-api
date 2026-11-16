<?php

namespace Database\Factories;

use App\Enums\OrganizationStatus;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->organizer(),
            'name' => fake()->company(),
            'description' => fake()->paragraph(),
            'verified' => false,
            'status' => OrganizationStatus::Pending,
        ];
    }

    public function approved(): static
    {
        return $this->state(['status' => OrganizationStatus::Approved, 'verified' => true]);
    }
}
