<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessGroup extends Model
{
    protected $table = 'access_groups';
    protected $primaryKey = 'access_group_id';
    
    protected $fillable = ['group_id', 'menu_id', 'action_id'];

    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id', 'group_id');
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class, 'menu_id', 'menu_id');
    }

    public function action()
    {
        return $this->belongsTo(Action::class, 'action_id', 'action_id');
    }

}