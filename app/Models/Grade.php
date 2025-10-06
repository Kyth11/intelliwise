<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    protected $fillable = [
        'q1','q2','q3','q4','final_grade','remark',
        'students_id','subjects_id','schoolyr_id','gradelvl_id','faculty_id'
    ];

    // Relationships (adjust class names if yours differ)
    public function student()    { return $this->belongsTo(Student::class,   'students_id'); }
    public function subject()    { return $this->belongsTo(Subjects::class,   'subjects_id'); }
    public function schoolYear() { return $this->belongsTo(Schoolyr::class,  'schoolyr_id'); }
    public function gradeLevel() { return $this->belongsTo(Gradelvl::class,  'gradelvl_id'); }
    public function faculty()    { return $this->belongsTo(Faculty::class,   'faculty_id'); }

    /** Compute final average (rounded) if missing */
    public function computedFinal(): ?int
    {
        $qs = collect([$this->q1,$this->q2,$this->q3,$this->q4])->filter(fn($v)=>$v!==null);
        if ($qs->count() === 0) return null;
        return (int) round($qs->avg());
    }

    /** DepEd K–12 static helpers for use in Blade */
    public static function depedDescriptor(?int $grade): ?array
    {
        if ($grade === null) return null;
        if ($grade >= 90) return ['Outstanding', 'O'];
        if ($grade >= 85) return ['Very Satisfactory', 'VS'];
        if ($grade >= 80) return ['Satisfactory', 'S'];
        if ($grade >= 75) return ['Fairly Satisfactory', 'FS'];
        return ['Did Not Meet Expectations', 'DNME'];
    }

    public static function depedRemark(?int $grade): ?string
    {
        if ($grade === null) return null;
        return $grade >= 75 ? 'PASSED' : 'FAILED';
    }
}
