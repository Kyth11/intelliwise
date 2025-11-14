<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Faculty extends Model
{

    use SoftDeletes;
    protected $table = 'faculties';

    protected $fillable = [
        'name',
        'username',
        'contact',
        'email',
        'schoolyr_id', // etc


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

    public function schoolyr(): BelongsTo
    {
        return $this->belongsTo(Schoolyr::class, 'schoolyr_id');
    }
}

