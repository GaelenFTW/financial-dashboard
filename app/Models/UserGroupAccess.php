<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserGroupAccess extends Model
{
    protected $table = 'user_group_access';
    protected $primaryKey = 'user_group_access_id';
    
    public $timestamps = true;

    protected $fillable = [
        'group_id',
        'user_id',
        'project_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id', 'group_id');
    }

    public function project()
    {
        return $this->belongsTo(MasterProject::class, 'project_id', 'project_id');
    }
}