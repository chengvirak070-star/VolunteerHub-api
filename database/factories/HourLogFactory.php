<?php

namespace Database\Factories;

use App\Enums\HourLogStatus;
use App\Models\EventRegistration;
use App\Models\HourLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HourLog>
 */
class HourLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_registration_id' => EventRegistration::factory()->attended(),
            // user_id, event_id and hours are copied from the registration
            'user_id' => fn (array $attrs) => EventRegistration::find($attrs['event_registration_id'])->user_id,
            'event_id' => fn (array $attrs) => EventRegistration::find($attrs['event_registration_id'])->event_id,
            'hours' => fn (array $attrs) => EventRegistration::find($attrs['event_registration_id'])->event->hours,
            'status' => HourLogStatus::Pending,
        ];
    }

    public function approved(): static
    {
        return $this->state(['status' => HourLogStatus::Approved, 'reviewed_at' => now()]);
    }
}
