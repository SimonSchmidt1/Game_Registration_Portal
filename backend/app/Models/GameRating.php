<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GameRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'project_id',
        'user_id',
        'rating'
    ];

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
