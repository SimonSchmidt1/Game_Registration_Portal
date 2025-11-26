<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        $types = ['game','web_app','mobile_app','library','other'];
        return [
            'team_id' => Team::factory(),
            'academic_year_id' => null,
            'title' => $this->faker->unique()->sentence(3),
            'description' => $this->faker->paragraph(),
            'type' => $this->faker->randomElement($types),
            'category' => $this->faker->randomElement(['Akčná','Strategická','RPG','Simulátor','Horor']),
            'release_date' => $this->faker->date(),
            'files' => [],
            'metadata' => [],
            'rating' => 0,
            'rating_count' => 0,
            'views' => 0,
        ];
    }
}
