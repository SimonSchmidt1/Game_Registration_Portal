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
        $schoolTypes = ['zs', 'ss', 'vs'];
        $subjects = ['Slovenský jazyk', 'Matematika', 'Dejepis', 'Geografia', 'Informatika', 'Grafika', 'Chémia', 'Fyzika'];
        $schoolType = $this->faker->randomElement($schoolTypes);
        
        return [
            'team_id' => Team::factory(),
            'academic_year_id' => null,
            'title' => $this->faker->unique()->sentence(3),
            'description' => $this->faker->paragraph(),
            'type' => $this->faker->randomElement($types),
            'school_type' => $schoolType,
            'year_of_study' => $schoolType === 'zs' ? $this->faker->numberBetween(1, 9) : $this->faker->numberBetween(1, 5),
            'subject' => $this->faker->randomElement($subjects),
            'release_date' => $this->faker->date(),
            'files' => [],
            'metadata' => [],
            'rating' => 0,
            'rating_count' => 0,
            'views' => 0,
        ];
    }
}
