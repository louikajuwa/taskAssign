<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Testplan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'testplan_id' => Testplan::factory(),
            'assignment_role_id' => 1,
            'target_user_id' => User::factory(),
            'author_id' => User::factory(),
            'comment' => fake()->sentence(),
            'comment_file' => null,
            'thread_id' => (string) Str::uuid(),
        ];
    }
}
