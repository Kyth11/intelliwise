<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Guardian extends Model
{
    protected $fillable = [
        'g_firstname',
        'g_middlename',
        'g_lastname',
        'g_address',
        'g_contact',
        'g_email',
    ];

    public function user()
    {
        return $this->hasOne(User::class, 'guardian_id');
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'guardian_id');
    }
}
