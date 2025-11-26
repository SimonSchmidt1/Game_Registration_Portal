<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;
    protected $fillable = [
        'team_id',
        'academic_year_id',
        'title',
        'description',
        'type',
        'category',
        'release_date',
        'splash_screen_path',
        'video_path',
        'video_url',
        'files',
        'rating',
        'rating_count',
        'views',
        'metadata',
    ];

    protected $casts = [
        'files' => 'array',
        'metadata' => 'array',
        'release_date' => 'date',
        'rating' => 'decimal:2',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(GameRating::class, 'project_id');
    }

    // Helper to get file path by key
    public function getFile($key)
    {
        return $this->files[$key] ?? null;
    }

    // Helper to get metadata by key
    public function getMeta($key, $default = null)
    {
        return $this->metadata[$key] ?? $default;
    }
}
