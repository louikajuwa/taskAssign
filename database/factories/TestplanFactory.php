<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Testplan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Testplan>
 */
class TestplanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'category_id' => Category::factory(),
            'case_information' => fake()->paragraph(),
            'version_type' => 'Beta',
            'version_number' => '1.0.0',
            'type_of_status' => null,
            'priority' => 'Medium',
            'main_status' => 'Open',
            'created_by' => User::factory(),
        ];
    }
}
