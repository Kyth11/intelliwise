<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'username',
        'password',
        'role',
        'guardian_id',
        'faculty_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // If you're on Laravel 10+, this will auto-hash assigned passwords:
    // (Safe to keep even if you still call bcrypt() manually.)
    protected $casts = [
        'password' => 'hashed',
    ];

/*************  ✨ Windsurf Command ⭐  *************/
/*******  3037e367-b073-405e-aa8e-d8ba7ab61f59  *******/
    public function guardian()
    {
        return $this->belongsTo(Guardian::class, 'guardian_id');
    }

    public function faculty()
    {
        return $this->belongsTo(Faculty::class, 'faculty_id');
    }
}
