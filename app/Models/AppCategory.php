<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppCategory extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function apps(): HasMany
    {
        return $this->hasMany(App::class, 'category_id');
    }
}
