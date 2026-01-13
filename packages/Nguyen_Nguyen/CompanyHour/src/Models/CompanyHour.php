<?php
namespace NguyenNguyen\CompanyHour\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyHour extends Model
{
    protected $fillable = ['start_at', 'end_at', 'lunch_start', 'lunch_end', 'mid_day'];
}
