<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class App extends Model
{
    protected $fillable = [
        "id",
        "client_id",
        "data"
    ];
    protected $casts = [
        "data" => "array"
    ];
}
