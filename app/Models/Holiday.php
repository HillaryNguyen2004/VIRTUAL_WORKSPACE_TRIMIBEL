<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $fillable = [
        'title', 
        'start_date', 
        'end_date'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date'   => 'datetime',
    ];

    // Accessor to format start_date for datetime-local input (removes seconds and timezone)
    public function getStartDateLocalAttribute()
    {
        return $this->start_date ? $this->start_date->format('Y-m-d\TH:i') : null;
    }

    // Accessor to format end_date for datetime-local input
    public function getEndDateLocalAttribute()
    {
        return $this->end_date ? $this->end_date->format('Y-m-d\TH:i') : null;
    }

    // Append these to JSON serialization
    protected $appends = ['start_date_local', 'end_date_local'];
}