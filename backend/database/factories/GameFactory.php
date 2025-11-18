<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Game;
use App\Models\Team;
use App\Models\AcademicYear;

class GameFactory extends Factory
{
    protected $model = Game::class;

    public function definition()
    {
        $team = Team::inRandomOrder()->first() ?: Team::factory()->create();
        $year = AcademicYear::inRandomOrder()->first() ?: AcademicYear::factory()->create();

        return [
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'release_date' => $this->faker->date(),
            'team_id' => $team->id,
            'academic_year_id' => $year->id,
            'trailer_path' => 'games/trailers/fake_trailer.mp4',
            'splash_screen_path' => 'games/splash_screens/fake_splash.jpg',
            'source_code_path' => 'games/source_codes/fake_code.zip',
            'export_path' => 'games/exports/fake_export.exe',
        ];
    }
}
