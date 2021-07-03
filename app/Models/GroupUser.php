<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Relations\Pivot;

class GroupUser extends Pivot
{
    use CrudTrait;

    public $incrementing = false;
    protected $primaryKey = null;

    public function user()
    {
        return $this->hasOne(User::class);
    }

    public function group()
    {
        return $this->hasOne(Group::class);
    }
}
