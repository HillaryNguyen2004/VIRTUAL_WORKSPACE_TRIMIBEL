<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChannelRule extends Model
{
    use HasFactory;

    protected $table = 'channel_rules';

    protected $fillable = [
        'channel_id',
        'title',
        'content',
        'created_by',
    ];

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }
}
