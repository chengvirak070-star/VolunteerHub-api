<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'event_id' => null,
            'body' => fake()->sentence(15),
            'status' => ContentStatus::Visible,
        ];
    }

    public function hidden(): static
    {
        return $this->state(['status' => ContentStatus::Hidden]);
    }

    /** Keep the hashtag pivot in sync for factory-made posts. */
    public function configure(): static
    {
        return $this->afterCreating(fn (Post $post) => $post->syncHashtags());
    }
}
