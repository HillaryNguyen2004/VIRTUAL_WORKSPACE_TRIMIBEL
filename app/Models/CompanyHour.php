<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyHour extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'start_at',
        'end_at',
        'lunch_start',
        'lunch_end',
        'mid_day',
        'working_days'
    ];
    
    protected $casts = [
        'working_days' => 'json',
    ];
    
    /**
     * Get the working days as an array
     */
    public function getWorkingDaysArray(): array
    {
        return $this->working_days ?? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    }
    
    /**
     * Check if a specific day is a working day
     */
    public function isWorkingDay(string $day): bool
    {
        return in_array($day, $this->getWorkingDaysArray());
    }
}

