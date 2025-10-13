<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $fillable = [
        'name', 'email', 'password', 'permissions','adminid'
    ];

    protected $casts = [
        'permissions' => 'array',
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function canUpload()
    {
        return $this->permissions == 1 || $this->permissions == 2; // ID 1 and 2 can upload
    }

    public function canView()
    {
        return $this->permissions == 1 || $this->permissions == 2 || $this->permissions == 3; // ID 1 and 3 can view
    }

    public function canExport()
    {
        return $this->permissions == 1 || $this->permissions == 3; // ID 1 and 3 can export
    }
}