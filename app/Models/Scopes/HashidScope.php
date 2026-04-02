<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class HashidScope implements Scope
{
    public function apply(Builder $builder, Model $model): void {}

    public function extend(Builder $builder): void
    {
        $builder->macro('findByHashid', function (Builder $builder, string $hashid) {
            return $builder->where(
                $builder->getModel()->qualifyColumn('hashid'),
                $hashid
            )->first();
        });

        $builder->macro('findByHashidOrFail', function (Builder $builder, string $hashid) {
            return $builder->where(
                $builder->getModel()->qualifyColumn('hashid'),
                $hashid
            )->firstOrFail();
        });
    }
}
