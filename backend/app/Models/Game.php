<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'release_date',
        'team_id',
        'academic_year_id',
        'trailer_path',
        'splash_screen_path',
        'source_code_path',
        'export_path',
        'category'
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
