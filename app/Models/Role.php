<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;

class Role extends \Spatie\Permission\Models\Role
{
    use CrudTrait;
}
