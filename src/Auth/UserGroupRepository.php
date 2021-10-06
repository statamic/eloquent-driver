<?php

namespace Statamic\Eloquent\Auth;

use Illuminate\Support\Collection as IlluminateCollection;
use Statamic\Auth\Eloquent\UserGroupRepository as BaseRepository;
use Statamic\Contracts\Auth\UserGroup as UserGroupContract;

class UserGroupRepository extends BaseRepository
{
    public function make(): UserGroupContract
    {
        return (new (app(UserGroupContract::class)));
    }

    public function save($userGroup)
    {
        $model = $userGroup->toModel();

        $model->save();

        $userGroup->model($model->fresh());
    }

    public function delete($userGroup)
    {
        $userGroup->model()->delete();
    }

    public static function bindings(): array
    {
        return [
            UserGroupContract::class => app('statamic.eloquent.groups.entry'),
        ];
    }

    protected function transform($items, $columns = [])
    {
        return IlluminateCollection::make($items)->map(function ($model) {
            return app(UserGroupContract::class)::fromModel($model);
        });
    }
    public function all(): IlluminateCollection
    {
        $class = app('statamic.eloquent.groups.model');
        return $this->transform($class::all());
    }

    public function find($id): ?UserGroupContract
    {
        $class = app('statamic.eloquent.groups.model');
        $model = $class::whereHandle($id)->first();

        return $model
            ? app(UserGroupContract::class)->fromModel($model)
            : null;
    }
}
