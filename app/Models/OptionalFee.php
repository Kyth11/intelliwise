<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class OptionalFee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'amount',
        'scope',
        'active',
        'schoolyr_id',
    ];

    protected $casts = [
        'active' => 'boolean',
        'amount' => 'decimal:2',
    ];

    protected $attributes = [
        'scope' => 'both',
    ];

    public function tuitions(): BelongsToMany
    {
        return $this->belongsToMany(
            Tuition::class,
            'tuition_optional_fee',
            'optional_fee_id',
            'tuition_id'
        )->withTimestamps();
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(
            Student::class,
            'optional_fee_student',
            'optional_fee_id',
            'student_id'
        )->withTimestamps();
    }

    public function schoolyr(): BelongsTo
    {
        return $this->belongsTo(Schoolyr::class, 'schoolyr_id');
    }

    /** Scope: only active optional fees. */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
