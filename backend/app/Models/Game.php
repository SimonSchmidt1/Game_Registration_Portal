<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Game extends Model
{
    use HasFactory, SoftDeletes;

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
        'category',
        'rating',
        'views',
        'rating_count'
    ];

    protected $casts = [
        'rating' => 'float',
        'views' => 'integer',
        'rating_count' => 'integer'
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function ratings()
    {
        return $this->hasMany(GameRating::class);
    }
}
