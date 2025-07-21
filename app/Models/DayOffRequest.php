<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DayOffRequest extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'date',
        'leave_type',
        'reason',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function teamMembers()
{
    return $this->hasMany(User::class, 'team_leader_id');
}
}
