<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schoolyr extends Model
{
 public function getDisplayLabelAttribute(): string
{
    $label = $this->school_year
        ?? $this->display_year
        ?? $this->year
        ?? $this->name;

    if (!$label) {
        $start = $this->start_year ?? $this->from_year ?? $this->from ?? $this->year_start ?? $this->sy_start ?? null;
        $end   = $this->end_year   ?? $this->to_year   ?? $this->to   ?? $this->year_end   ?? $this->sy_end   ?? null;
        if ($start || $end) $label = trim(($start ?? '').'–'.($end ?? ''));
    }

    if (!$label) {
        $label = $this->created_at ? $this->created_at->year.'–'.($this->created_at->year + 1) : 'SY #'.$this->id;
    }

    return $label;
}
   
}
