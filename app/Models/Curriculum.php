<?php
// app/Models/Curriculum.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Curriculum extends Model
{
    protected $table = 'curriculum';

    protected $fillable = [
        'schoolyr_id',
        'grade_id',
        'adviser_id',
        'curriculum_name',
        'deleted',
        'status',
    ];

    public function schoolYear()
    {
        return $this->belongsTo(Schoolyr::class, 'schoolyr_id');
    }

    public function grade()
    {
        return $this->belongsTo(Gradelvl::class, 'grade_id');
    }

    public function adviser()
    {
        return $this->belongsTo(Faculty::class, 'adviser_id');
    }

    // direct access to pivot rows
    public function children()
    {
        return $this->hasMany(CurriculumChild::class, 'curriculum_id');
    }

    // main relationship: curriculum → many subjects via curriculum_child
    public function subjects()
    {
        return $this->belongsToMany(Subjects::class, 'curriculum_child', 'curriculum_id', 'subject_id')
            ->withPivot([
                'deleted',
                'status',
                'day_schedule',
                'class_start',
                'class_end',
            ])
            ->withTimestamps();
    }
}
