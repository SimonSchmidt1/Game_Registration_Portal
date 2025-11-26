<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TeamFactory extends Factory
{
    protected $model = Team::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->company(),
            'academic_year_id' => AcademicYear::factory(),
            'invite_code' => Str::upper(Str::random(6)),
        ];
    }
}
