<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faculty extends Model
{
    use HasFactory;

    protected $table = 'faculty'; // or 'faculties' if that's your table name

    protected $fillable = [
        'f_firstname',
        'f_middlename',
        'f_lastname',
        'f_address',
        'f_contact',
        'f_email',
    ];

    // Relationship to User
    public function user()
    {
        return $this->hasOne(User::class, 'faculty_id');
    }
}
