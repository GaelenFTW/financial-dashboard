<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $table = 'menus';
    protected $primaryKey = 'menu_id';
    
    protected $fillable = ['parent', 'menu_name', 'link', 'active', 'deleted'];
    protected $casts = ['active' => 'boolean', 'deleted' => 'boolean'];

    public function actions()
    {
        return $this->hasMany(Action::class, 'menu_id', 'menu_id');
    }

    public function accessGroups()
    {
        return $this->hasMany(AccessGroup::class, 'menu_id', 'menu_id');
    }

    // Get active menus only
    public function scopeActive($query)
    {
        return $query->where('active', 1)->where('deleted', 0);
    }

    // Get menus by parent
    public function scopeByParent($query, $parent)
    {
        return $query->where('parent', $parent);
    }

    // Get all active actions for this menu
    public function getActiveActions()
    {
        return $this->actions()->where('active', 1)->get();
    }
}
