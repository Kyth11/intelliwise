<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payments extends Model
{
    protected $table = 'payments';

    protected $fillable = [
        'amount',
        'payment_method',   // 'Cash' | 'G-cash'
        'payment_status',   // 'Paid' | 'Unpaid' | 'Partial'
        'balance',
        'tuition_id',
    ];

    protected $casts = [
        'amount'  => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public function tuition(): BelongsTo
    {
        return $this->belongsTo(Tuition::class, 'tuition_id');
    }
}
