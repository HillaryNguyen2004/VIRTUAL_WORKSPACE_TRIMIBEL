<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = ['name', 'subject', 'content'];

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }
}
