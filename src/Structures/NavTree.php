<?php

namespace Statamic\Eloquent\Structures;

use Statamic\Eloquent\Structures\TreeModel as Model;
use Statamic\Structures\NavTree as FileEntry;

class NavTree extends FileEntry
{
    protected $model;

    public static function fromModel(Model $model)
    {
        return (new static)
            ->tree($model->tree)
            ->handle($model->handle)
            ->locale($model->locale)
            ->initialPath($model->initial_path)
            ->model($model);
    }

    public function toModel()
    {
        return self::makeModelFromContract($this);
    }

    public static function makeModelFromContract($source)
    {
        $class = app('statamic.eloquent.navigations.tree_model');

        return $class::findOrNew($source instanceof FileEntry ? null : $source->model?->id)
            ->fill([
                'handle' => $source->handle(),
                'initial_path' => $source->initialPath(),
                'locale' => $source->locale(),
                'tree' => $source->tree() ?? [],
                'type' => 'navigation',
            ]);
    }

    public function model($model = null)
    {
        if (func_num_args() === 0) {
            return $this->model;
        }

        $this->model = $model;

        return $this;
    }
}
