<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Action extends Model
{
    protected $table = 'actions';
    protected $primaryKey = 'action_id';
    
    protected $fillable = ['menu_id', 'action', 'active'];
    protected $casts = ['active' => 'boolean'];

    public function menu()
    {
        return $this->belongsTo(Menu::class, 'menu_id', 'menu_id');
    }

    public function accessGroups()
    {
        return $this->hasMany(AccessGroup::class, 'action_id', 'action_id');
    }

    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }
}