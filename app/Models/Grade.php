<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Grade extends Model
{
    protected $table = 'grades';

    protected $fillable = [
        'q1','q2','q3','q4','final_grade','remark',
        'student_id','students_id','subject_id','subjects_id',
        'schoolyr_id','gradelvl_id','faculty_id'
    ];

    /** Resolve FK names based on actual DB columns */
    public static function studentKey(): string
    {
        static $key;
        if ($key === null) {
            $key = Schema::hasColumn('grades', 'student_id') ? 'student_id' : 'students_id';
        }
        return $key;
    }

    public static function subjectKey(): string
    {
        static $key;
        if ($key === null) {
            $key = Schema::hasColumn('grades', 'subject_id') ? 'subject_id' : 'subjects_id';
        }
        return $key;
    }

    // Relationships (Subject vs Subjects model name both supported)
    public function student()
    {
        return $this->belongsTo(Student::class, self::studentKey());
    }

    public function subject()
    {
        $class = class_exists(\App\Models\Subjects::class)
            ? \App\Models\Subjects::class
            : (class_exists(\App\Models\Subjects::class) ? \App\Models\Subjects::class : Model::class);

        return $this->belongsTo($class, self::subjectKey());
    }

    public function schoolYear() { return $this->belongsTo(Schoolyr::class, 'schoolyr_id'); }
    public function gradeLevel() { return $this->belongsTo(Gradelvl::class, 'gradelvl_id'); }
    public function faculty()    { return $this->belongsTo(Faculty::class,  'faculty_id'); }

    /**
     * New rule:
     * - Each quarter is 25% (missing = 0)
     * - If final <= 70 => 0 (else round)
     */
    public function computedFinal(): ?int
    {
        $vals = [
            (int)($this->q1 ?? 0),
            (int)($this->q2 ?? 0),
            (int)($this->q3 ?? 0),
            (int)($this->q4 ?? 0),
        ];

        $finalFloat = array_sum($vals) / 4;
        return ($finalFloat <= 70) ? 0 : (int) round($finalFloat);
    }

    /** DepEd helpers */
    public static function depedDescriptor(?int $g): ?array
    {
        if ($g === null) return null;
        if ($g >= 90) return ['Outstanding','O'];
        if ($g >= 85) return ['Very Satisfactory','VS'];
        if ($g >= 80) return ['Satisfactory','S'];
        if ($g >= 75) return ['Fairly Satisfactory','FS'];
        return ['Did Not Meet Expectations','DNME'];
    }

    public static function depedRemark(?int $g): ?string
    {
        if ($g === null) return null;
        return $g >= 75 ? 'PASSED' : 'FAILED';
    }
}
