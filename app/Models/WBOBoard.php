<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WBOBoard extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'board_id',
        'board_data',
        'last_accessed_at',
    ];
}
