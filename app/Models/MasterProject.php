<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MasterProject extends Model
{
    protected $fillable = [
        'project_id',
        'name',
        'description',
        'code',
        'sh',
        'is_active',
    ];


    /**
     * Get the users that belong to the project.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user', 'project_id', 'user_id')
            ->withPivot('role')
            ->withTimestamps();
    }
}
