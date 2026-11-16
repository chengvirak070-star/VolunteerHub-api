<?php

namespace Database\Factories;

use App\Enums\BugReportStatus;
use App\Models\BugReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BugReport>
 */
class BugReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'message' => fake()->sentence(12),
            'page_url' => fake()->randomElement(['/', '/discover', '/events/1', '/u/sokha']),
            'status' => BugReportStatus::Open,
        ];
    }
}
