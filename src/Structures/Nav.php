<?php

namespace Statamic\Eloquent\Structures;

use Statamic\Eloquent\Structures\NavModel as Model;
use Statamic\Structures\Nav as FileEntry;

class Nav extends FileEntry
{
    protected $model;

    public static function fromModel(Model $model)
    {
        return (new static)
            ->handle($model->handle)
            ->title($model->title)
            ->collections($model->collections)
            ->maxDepth($model->maxDepth)
            ->expectsRoot($model->expectsRoot)
            ->initialPath($model->initialPath)
            ->model($model);
    }

    public function newTreeInstance()
    {
        return new NavTree;
    }

    public function toModel()
    {
        $class = app('statamic.eloquent.navigations.model');

        return $class::findOrNew($this->model?->id)->fill([
            'handle' => $this->handle(),
            'title' => $this->title(),
            'collections' => $this->collections()->map->handle(),
            'maxDepth' => $this->maxDepth(),
            'expectsRoot' => $this->expectsRoot(),
            'initialPath' => $this->initialPath(),
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
