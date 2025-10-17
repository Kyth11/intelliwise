<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faculty extends Model
{
    protected $table = 'faculties';

    protected $fillable = [
        'f_firstname',
        'f_middlename',
        'f_lastname',
        'f_address',
        'f_contact',
        'f_email',
    ];

    public function user()
    {
        return $this->hasOne(User::class, 'faculty_id');
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim(collect([$this->f_firstname, $this->f_middlename, $this->f_lastname])->filter()->implode(' '));
    }
}
