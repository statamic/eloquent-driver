<?php

namespace Statamic\Eloquent\Auth;

use Illuminate\Support\Collection as IlluminateCollection;
use Statamic\Auth\RoleRepository as BaseRepository;
use Statamic\Contracts\Auth\Role as RoleContract;

class RoleRepository extends BaseRepository
{
    public function make(string $handle = null): RoleContract
    {
        return (new (app(RoleContract::class)))->handle($handle);
    }

    public function save($role)
    {
        $model = $role->toModel();

        $model->save();

        $role->model($model->fresh());
    }

    public function delete($role)
    {
        $role->model()->delete();
    }

    public static function bindings(): array
    {
        return [
            RoleContract::class => app('statamic.eloquent.roles.entry'),
        ];
    }

    protected function transform($items, $columns = [])
    {
        return IlluminateCollection::make($items)->map(function ($model) {
            return app(RoleContract::class)::fromModel($model);
        });
    }
    public function all(): IlluminateCollection
    {
        $class = app('statamic.eloquent.roles.model');
        return $this->transform($class::all());
    }

    public function find($handle): ?RoleContract
    {
        $class = app('statamic.eloquent.roles.model');
        $model = $class::whereHandle($handle)->first();

        return $model
            ? app(RoleContract::class)->fromModel($model)
            : null;
    }

    public function findByHandle($handle): ?RoleContract
    {
        $class = app('statamic.eloquent.roles.model');
        $model = $class::whereHandle($handle)->first();

        return $model
            ? app(RoleContract::class)->fromModel($model)
            : null;
    }
}
