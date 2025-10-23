<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Project extends Model
{
    protected $table = 'master_project';
    protected $primaryKey = 'project_id';
    protected $fillable = ['sh', 'code', 'name'];
    public $timestamps = true;

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'project_user',
            'project_id',
            'user_id',
            'project_id',
            'id'
        )->withTimestamps();
    }

    public function userGroupAccess()
    {
        return $this->hasMany(UserGroupAccess::class, 'project_id', 'project_id');
    }
    
    public function getUsersWithGroups()
    {
        return $this->users()->with('groups')->get();
    }
}
