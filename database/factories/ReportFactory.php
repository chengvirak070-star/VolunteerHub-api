<?php

namespace Database\Factories;

use App\Enums\ReportStatus;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'reporter_id' => User::factory(),
            'reportable_type' => 'post',
            'reportable_id' => Post::factory(),
            'reason' => fake()->randomElement(['Spam', 'Offensive language', 'Fake event', 'Not related to volunteering']),
            'status' => ReportStatus::Open,
        ];
    }
}
