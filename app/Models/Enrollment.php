<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    protected $table = 'enrollment';

    protected $fillable = [
        'status','payment_status',
        'student_id','guardian_id','tuition_id',
        'schoolyr_id','gradelvl_id','faculty_id',
        'date_enrolled','date_dropped',
        'enrollment_type','remarks','is_active',
        'base_tuition','optional_total','total_due','paid_to_date','balance_cached',
    ];

    public function student(): BelongsTo  { return $this->belongsTo(Student::class); }
    public function guardian(): BelongsTo { return $this->belongsTo(Guardian::class); }
    public function tuition(): BelongsTo  { return $this->belongsTo(Tuition::class); }
    public function gradelvl(): BelongsTo { return $this->belongsTo(Gradelvl::class, 'gradelvl_id'); }
    public function faculty(): BelongsTo  { return $this->belongsTo(Faculty::class, 'faculty_id'); }
    public function schoolyr(): BelongsTo { return $this->belongsTo(Schoolyr::class, 'schoolyr_id'); }
}
