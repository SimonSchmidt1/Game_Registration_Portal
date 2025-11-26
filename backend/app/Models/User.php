<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'team_id',
        'avatar_path',
        'verification_token',
        'failed_login_attempts',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class)->withPivot('role_in_team')->withTimestamps();
    }

    /**
     * Check if user has admin role.
     * Foundation for any admin functionality.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is Scrum Master of a specific team.
     */
    public function isScrumMasterOf(int $teamId): bool
    {
        return $this->teams()
            ->wherePivot('team_id', $teamId)
            ->wherePivot('role_in_team', 'scrum_master')
            ->exists();
    }

    /**
     * Check if user is Scrum Master of any team.
     */
    public function isScrumMasterOfAnyTeam(): bool
    {
        return $this->teams()
            ->wherePivot('role_in_team', 'scrum_master')
            ->exists();
    }

}
