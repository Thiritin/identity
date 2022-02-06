<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Backpack\CRUD\app\Models\Traits\SpatieTranslatable\HasTranslations;
use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Group.
 *
 * @property string $id
 * @property string $name
 * @property string $logo
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection|User[] $users
 * @property-read int|null $users_count
 * @method static Builder|Group newModelQuery()
 * @method static Builder|Group newQuery()
 * @method static Builder|Group query()
 * @method static Builder|Group whereCreatedAt($value)
 * @method static Builder|Group whereId($value)
 * @method static Builder|Group whereLogo($value)
 * @method static Builder|Group whereName($value)
 * @method static Builder|Group whereUpdatedAt($value)
 * @mixin Eloquent
 */
class Group extends Model
{
    use CrudTrait;
    use HasFactory;
    use HasTranslations;

    public $translatable = ['name', 'description'];

    protected $fillable = [
        'internal_name',
        'description',
        'name',
        'type',
        'logo',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->using('App\Models\GroupUser')
            ->withPivot(
                [
                    'title',
                    'authorization_level',
                    'is_director',
                ]
            );
    }
}
