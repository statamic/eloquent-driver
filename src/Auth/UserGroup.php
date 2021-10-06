<?php

namespace Statamic\Eloquent\Auth;

use Statamic\Auth\Eloquent\UserGroup as EloquentUserGroup;

class UserGroup extends EloquentUserGroup
{
    protected $model;

    public static function fromModel(UserGroupModel $model)
    {
        return (new static)
            ->title($model->title)
            ->handle($model->handle)
            ->roles($model->roles)
            ->model($model);
    }

    public function toModel()
    {
        $class = app('statamic.eloquent.groups.model');

        return $class::findOrNew($this->model?->id)->fill([
            'title' => $this->title,
            'handle' => $this->handle,
            'roles' => $this->roles->keys(),
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
