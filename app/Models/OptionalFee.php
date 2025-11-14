<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class OptionalFee extends Model
{

    use SoftDeletes;

    protected $fillable = ['name', 'amount', 'active', 'schoolyr_id',];

    protected $casts = [
        'active' => 'boolean',
        'amount' => 'decimal:2',
    ];

    public function tuitions(): BelongsToMany
    {
        return $this->belongsToMany(Tuition::class, 'tuition_optional_fee')->withTimestamps();
    }

    /**
     * ✅ Pivot table is `optional_fee_student`
     * columns: optional_fee_id, student_id
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(
            Student::class,
            'optional_fee_student',   // <- correct table name
            'optional_fee_id',        // FK to optional_fees
            'student_id'              // FK to students
        )->withTimestamps();
        // ->withPivot('amount_override'); // not present in your schema
    }
    public function schoolyr(): BelongsTo
    {
        return $this->belongsTo(Schoolyr::class, 'schoolyr_id');
    }
}
