<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subjects extends Model
{
    // If your table is named "subjects", you can skip this
    protected $table = 'subjects';

    // Fields you allow to be mass assigned
    protected $fillable = [
        'subject_name',
        'subject_code',
        'description',
    ];

    /**
     * A subject can have many schedules.
     */
    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'subject_id');
    }
}
