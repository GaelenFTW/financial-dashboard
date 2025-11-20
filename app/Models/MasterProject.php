<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterProject extends Model
{
    protected $table = 'master_project';
    protected $primaryKey = 'project_id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'project_id',
        'sh',
        'code',
        'name',
    ];

    public function getRouteKeyName()
    {
        return 'project_id';
    }

    // Many-to-many relationship with users
    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'user_group_access',
            'project_id',
            'user_id',
            'project_id',
            'id'
        )->withTimestamps();
    }

    public function userGroupAccesses()
    {
        return $this->hasMany(UserGroupAccess::class, 'project_id', 'project_id');
    }
}
