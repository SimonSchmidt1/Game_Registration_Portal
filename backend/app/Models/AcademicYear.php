<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // <--- toto musí byť
use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    use HasFactory;

    protected $fillable = ['name'];
}
