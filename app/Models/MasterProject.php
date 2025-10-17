<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MasterProject extends Model
{
    protected $table = 'master_project';  // ✅ not plural
    protected $primaryKey = 'project_id'; // ✅ correct key
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false; // 🚫 disable created_at & updated_at


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

    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'project_user',
            'project_id',
            'user_id'
        )->withPivot('role')->withTimestamps();
    }
}
