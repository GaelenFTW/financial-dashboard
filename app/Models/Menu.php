<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $table = 'menus';
    protected $primaryKey = 'menu_id';
    
    protected $fillable = [
        'name',
        'link',
        'parent_id',
        'sort_order',
        'active',
        'deleted'
    ];

    protected $casts = [
        'active' => 'boolean',
        'deleted' => 'boolean',
        'sort_order' => 'integer',
        'parent_id' => 'integer',
    ];

    public function parent()
    {
        return $this->belongsTo(Menu::class, 'parent_id', 'menu_id');
    }

    public function children()
    {
        return $this->hasMany(Menu::class, 'parent_id', 'menu_id');
    }

    public function actions()
    {
        return $this->hasMany(Action::class, 'menu_id', 'menu_id');
    }

    public function accessGroups()
    {
        return $this->hasMany(AccessGroup::class, 'menu_id', 'menu_id');
    }

    // Scope to get only active menus
    public function scopeActive($query)
    {
        return $query->where('active', true)->where('deleted', false);
    }
}