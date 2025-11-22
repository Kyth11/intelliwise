<?php
// app/Models/CurriculumChild.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurriculumChild extends Model
{
    protected $table = 'curriculum_child';

    protected $fillable = [
        'curriculum_id',
        'subject_id',
        'deleted',
        'status',
        'day_schedule',
        'class_start',
        'class_end',
    ];

    public function curriculum()
    {
        return $this->belongsTo(Curriculum::class, 'curriculum_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subjects::class, 'subject_id');
    }
}
