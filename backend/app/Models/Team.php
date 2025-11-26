<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'invite_code',
        'academic_year_id',
        'scrum_master_id',
    ];

    /**
     * Získava členov (používateľov) tímu.
     */
    public function members(): BelongsToMany
    {
        // ✅ Oprava: Používame withPivot('role_in_team') pre správne načítanie roly (member/Scrum Master)
        // Zároveň je potrebné odstrániť ->select(), aby sa pivot dáta správne načítali pre ďalšie operácie.
        return $this->belongsToMany(User::class, 'team_user')
                    ->withPivot('role_in_team'); 
    }
    
    /**
     * Získava akademický rok tímu.
     */
    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Získava všetky hry tímu.
     */
    public function games()
    {
        return $this->hasMany(Game::class);
    }
}