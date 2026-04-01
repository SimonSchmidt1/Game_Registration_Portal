<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuthorizedStudent extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'student_id',
        'name',
        'email',
        'student_type',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Check if email is authorized for registration
     *
     * @param  string  $email
     * @return bool
     */
    public static function isAuthorized(string $email): bool
    {
        return self::where('email', $email)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Get authorized student by email
     *
     * @param  string  $email
     * @return \App\Models\AuthorizedStudent|null
     */
    public static function getByEmail(string $email): ?self
    {
        return self::where('email', $email)
            ->where('is_active', true)
            ->first();
    }
}
