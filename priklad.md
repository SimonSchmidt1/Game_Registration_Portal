# Príklad formátovania pre Microsoft Word

Tento kód bol horizontálne zhustený (odstránené zbytočné zvislé medzery a funkcie napísané na jeden riadok). Vďaka tomu zaberie vo Worde podstatne menej miesta a bude pôsobiť úhľadne.

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    // Horizontálne zarovnané polia pre mass assignment
    protected $fillable = [
        'team_id', 'academic_year_id', 'title', 'description', 'type', 'school_type', 
        'year_of_study', 'subject', 'predmet', 'release_date', 'splash_screen_path', 
        'video_path', 'video_url', 'files', 'rating', 'rating_count', 'views', 'metadata'
    ];

    // Horizontálne zarovnané polia pre automatickú transformáciu
    protected $casts = [
        'files' => 'array', 'metadata' => 'array', 
        'release_date' => 'date', 'rating' => 'decimal:2'
    ];

    // Relácie (skrátené na jeden riadok)
    public function team(): BelongsTo { return $this->belongsTo(Team::class); }
    public function academicYear(): BelongsTo { return $this->belongsTo(AcademicYear::class); }
    public function ratings(): HasMany { return $this->hasMany(GameRating::class, 'project_id'); }

    // Pomocné funkcie (skrátené na jeden riadok)
    public function getFile($key) { return $this->files[$key] ?? null; }
    public function getMeta($key, $default = null) { return $this->metadata[$key] ?? $default; }
}
```
