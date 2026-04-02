<?php

namespace App\Models\Concerns;

use App\Models\Scopes\HashidScope;
use Hashids\Hashids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * @method static static|null findByHashid(string $hashid)
 * @method static static findByHashidOrFail(string $hashid)
 */
trait HasHashid
{
    public static function bootHasHashid(): void
    {
        static::addGlobalScope(new HashidScope());

        static::created(function (Model $model) {
            if (empty($model->attributes['hashid'])) {
                $hashid = $model->generateHashid();
                DB::table($model->getTable())
                    ->where($model->getKeyName(), $model->getKey())
                    ->update(['hashid' => $hashid]);
                $model->attributes['hashid'] = $hashid;
            }
        });
    }

    public function getHashidAttribute(?string $value): string
    {
        if (! empty($value)) {
            return $value;
        }

        if ($this->exists && $this->getKey()) {
            $hashid = $this->generateHashid();
            DB::table($this->getTable())
                ->where($this->getKeyName(), $this->getKey())
                ->update(['hashid' => $hashid]);
            $this->attributes['hashid'] = $hashid;

            return $hashid;
        }

        return '';
    }

    public function getRouteKey(): string
    {
        return $this->hashid;
    }

    public function getRouteKeyName(): string
    {
        return 'hashid';
    }

    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        if ($field && $field !== 'hashid') {
            return parent::resolveRouteBindingQuery($query, $value, $field);
        }

        return $query->where($this->qualifyColumn('hashid'), $value);
    }

    public function getHashidsConnection(): string
    {
        return config('hashids.default');
    }

    public function generateHashid(): string
    {
        $connection = $this->getHashidsConnection();
        $config = config("hashids.connections.{$connection}");

        $hashids = new Hashids(
            $config['salt'],
            $config['length'],
            $config['alphabet'],
        );

        return $hashids->encode($this->getKey());
    }
}
