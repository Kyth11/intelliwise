<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use SoftDeletes;

    // Primary key is LRN (string)
    protected $primaryKey = 'lrn';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'lrn',
        's_firstname','s_middlename','s_lastname',
        's_birthdate','s_address','s_citizenship','s_religion',
        's_contact','s_email',
        'guardian_id',
        's_gradelvl','gradelvl_id',
        's_tuition_sum','tuition_id',
        's_optional_total','s_total_due',
        'enrollment_status','payment_status',
        'schoolyr_id',
        's_gender','previous_school','sped_has','sped_desc',
    ];

    protected $casts = [
        's_optional_total' => 'decimal:2',
        's_total_due'      => 'decimal:2',
    ];

    protected $appends = ['full_name'];

    public function guardian(): BelongsTo { return $this->belongsTo(Guardian::class, 'guardian_id'); }
    public function gradelvl(): BelongsTo { return $this->belongsTo(Gradelvl::class, 'gradelvl_id'); }
    public function tuition(): BelongsTo { return $this->belongsTo(Tuition::class, 'tuition_id'); }

    public function optionalFees(): BelongsToMany
    {
        // Pivot now stores LRN in optional_fee_student.student_id
        return $this->belongsToMany(
            OptionalFee::class,
            'optional_fee_student',
            'student_id',
            'optional_fee_id'
        )->withTimestamps();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payments::class, 'tuition_id', 'tuition_id');
    }

    public function getFullNameAttribute(): string
    {
        $mid = trim((string)($this->s_middlename ?? ''));
        $name = trim(implode(' ', array_filter([$this->s_firstname ?? '', $mid, $this->s_lastname ?? ''])));
        return $name !== '' ? $name : ('Student '.$this->lrn);
    }

    public function getBaseTuitionAttribute(): float
    {
        if ($this->relationLoaded('tuition') && $this->tuition) {
            return (float) $this->tuition->total_yearly;
        }
        if (!empty($this->s_tuition_sum)) {
            return (float) preg_replace('/[^\d.]+/', '', (string) $this->s_tuition_sum);
        }
        $row = Tuition::where('grade_level', $this->s_gradelvl)
                ->orderByDesc('updated_at')->orderByDesc('created_at')->first();
        return (float) ($row->total_yearly ?? 0);
    }

    public function getOptionalSumAttribute(): float
    {
        $fees = $this->relationLoaded('optionalFees') ? $this->optionalFees : $this->optionalFees()->get();
        return (float) $fees->sum(fn ($f) => (float) ($f->amount ?? 0));
    }

    public function getTotalDueAttribute(): float
    {
        return round($this->base_tuition + $this->optional_sum, 2);
    }

    public function getComputedBalanceAttribute(): float
    {
        $paid = (float) ($this->payments()->sum('amount') ?? 0);
        $bal  = ($this->base_tuition + $this->optional_sum) - $paid;
        return $bal > 0 ? round($bal, 2) : 0.0;
    }

    public function schoolyr(): BelongsTo { return $this->belongsTo(Schoolyr::class, 'schoolyr_id'); }
}
