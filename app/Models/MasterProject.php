<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MasterProject extends Model
{
    protected $connection = 'sqlsrv'; // 👈 ensures it uses SQL Server
    protected $table = 'master_project'; // 👈 your actual table name

    protected $primaryKey = 'project_id'; // 👈 if project_id is your key
    public $timestamps = false;

    protected $fillable = [
        'project_id',
        'sh',
        'code',
        'name',
        'description',
    ];

        public function getRouteKeyName()
    {
        return 'project_id';
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user', 'project_id', 'user_id')
            ->withPivot('role')
            ->withTimestamps();
    }
}
