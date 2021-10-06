<?php

namespace Statamic\Eloquent\Auth;

use Statamic\Auth\Eloquent\Role as EloquentRole;

class Role extends EloquentRole
{
    protected $model;

    public static function fromModel(RoleModel $model)
    {
        return (new static)
            ->title($model->title)
            ->handle($model->handle)
            ->permissions($model->permissions)
            ->preferences($model->preferences)
            ->model($model);
    }

    public function toModel()
    {
        $class = app('statamic.eloquent.roles.model');

        return $class::findOrNew($this->model?->id)->fill([
            'title' => $this->title,
            'handle' => $this->handle,
            'permissions' => $this->permissions,
            'preferences' => $this->preferences,
        ]);
    }

    public function model($model = null)
    {
        if (func_num_args() === 0) {
            return $this->model;
        }

        $this->model = $model;

        $this->id($model->id);

        return $this;
    }
}