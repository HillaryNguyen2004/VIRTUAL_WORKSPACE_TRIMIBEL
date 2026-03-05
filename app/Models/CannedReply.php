<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CannedReply extends Model
{
    use HasFactory;

    protected $table = 'canned_replies';

    protected $fillable = [
        'shortcut',
        'title',
        'body',
        'category',
        'created_by_user_id',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where('shortcut', 'like', "%{$term}%")
            ->orWhere('title', 'like', "%{$term}%");
    }
}
