<?php

namespace Database\Factories;

use App\Models\ForumPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ForumPost>
 */
class ForumPostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(6),
            'body' => [
                ['type' => 'text', 'content' => fake()->paragraphs(3, true)],
            ],
            'image_path' => null,
            'pinned' => false,
        ];
    }

    public function pinned(): static
    {
        return $this->state(fn () => ['pinned' => true]);
    }
}
