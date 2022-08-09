<?php

namespace Statamic\Eloquent\Structures;

use Statamic\Contracts\Structures\Nav as Contract;
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
            ->maxDepth($model->max_depth)
            ->expectsRoot($model->expects_root)
            ->initialPath($model->initial_path)
            ->model($model);
    }

    public function newTreeInstance()
    {
        return app(app('statamic.eloquent.navigations.tree'));
    }

    public function toModel()
    {
        return self::makeModelFromContract($this);
    }

    public static function makeModelFromContract(Contract $source)
    {
        $class = app('statamic.eloquent.navigations.model');

        return $class::firstOrNew(['handle' => $source->handle()])->fill([
            'handle' => $source->handle(),
            'title' => $source->title(),
            'collections' => $source->collections()->map->handle(),
            'max_depth' => $source->maxDepth(),
            'expects_root' => $source->expectsRoot(),
            'initial_path' => $source->initialPath(),
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
