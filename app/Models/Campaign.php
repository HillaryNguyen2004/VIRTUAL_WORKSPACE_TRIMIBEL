<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    // use HasFactory;
    protected $fillable = ['name', 'subject', 'content', 'scheduled_at'];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
