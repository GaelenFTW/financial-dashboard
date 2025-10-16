<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Project extends Model
{
    protected $table = 'master_project';
    protected $primaryKey = 'project_id';
    protected $fillable = ['sh', 'code', 'name'];
    public $timestamps = false;

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
}
