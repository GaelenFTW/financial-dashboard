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
        return $this->id == 1 || $this->id == 2; // ID 1 and 2 can upload
    }

    public function canView()
    {
        return $this->id == 1 || $this->id == 2|| $this->id == 3; // ID 1 and 3 can view
    }

    public function canExport()
    {
        return $this->id == 1 || $this->id == 3; // ID 1 and 3 can export
    }
}