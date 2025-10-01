<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name', 'username', 'password', 'role', 'guardian_id', 'faculty_id'
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    // Correct relationships
    public function guardian()
    {
        return $this->belongsTo(Guardian::class, 'guardian_id');
    }

    public function faculty()
    {
        return $this->belongsTo(Faculty::class, 'faculty_id');
    }
}
