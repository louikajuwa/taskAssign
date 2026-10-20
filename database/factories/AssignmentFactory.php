<?php

namespace Database\Factories;

use App\Models\Assignment;
use App\Models\Testplan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assignment>
 */
class AssignmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'testplan_id' => Testplan::factory(),
            'assigned_by' => User::factory(),
            'user_id' => User::factory(),
            'role_id' => 1,
            'role_name' => 'Developer',
            'level' => 0,
            'status' => 'pending',
            'what_status' => 'pending',
            'go_live' => null,
            'description' => null,
            'comment_thread_id' => null,
            'file_info' => null,
            'field_name' => 'asignToDeVId',
        ];
    }
}
