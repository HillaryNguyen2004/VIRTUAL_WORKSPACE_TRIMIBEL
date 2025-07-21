<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckIn extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_name',
        'date',
        'check_in_time',
        'check_out_time',
    ];
    public function user()
{
    return $this->belongsTo(User::class, 'user_name', 'name');
    // Or use 'user_id', 'id' depending on your DB schema
}

}
