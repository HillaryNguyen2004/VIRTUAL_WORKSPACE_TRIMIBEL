<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;
class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'department_permission')
            ->withTimestamps();
    }

    public function rolePermissions()
{
    return $this->belongsToMany(
        Permission::class,
        'department_role_permissions',
        'department_id',
        'permission_id'
    )->withPivot('role_name')->withTimestamps();
}
}
