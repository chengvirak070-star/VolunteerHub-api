<?php

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Models\Category;
use App\Models\Event;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    public const LOCATIONS = [
        'Riverside, Phnom Penh',
        'Toul Kork, Phnom Penh',
        'Sen Sok, Phnom Penh',
        'Chroy Changvar, Phnom Penh',
        'Daun Penh, Phnom Penh',
        'Russey Keo, Phnom Penh',
        'Chamkarmon, Phnom Penh',
        'Puok, Siem Reap',
        'Siem Reap City, Siem Reap',
        'Kep Beach, Kep',
        'Battambang City, Battambang',
        'Kampot Town, Kampot',
    ];

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('+2 days', '+60 days');
        $start->setTime(fake()->randomElement([7, 8, 9, 13, 14]), 0);
        $hours = fake()->randomElement([2, 3, 4, 5]);

        return [
            'organization_id' => Organization::factory()->approved(),
            'category_id' => Category::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraphs(2, true),
            'location' => fake()->randomElement(self::LOCATIONS),
            'starts_at' => $start,
            'ends_at' => (clone $start)->modify("+{$hours} hours"),
            'hours' => $hours,
            'capacity' => fake()->randomElement([10, 20, 30, 50]),
            'status' => EventStatus::Open,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => EventStatus::Pending]);
    }

    public function capacity(int $capacity): static
    {
        return $this->state(['capacity' => $capacity]);
    }

    /** An event that already happened. */
    public function past(): static
    {
        return $this->state(function () {
            $start = fake()->dateTimeBetween('-150 days', '-3 days');
            $start->setTime(8, 0);

            return [
                'starts_at' => $start,
                'ends_at' => (clone $start)->modify('+4 hours'),
                'hours' => 4,
                'status' => EventStatus::Closed,
            ];
        });
    }
}
