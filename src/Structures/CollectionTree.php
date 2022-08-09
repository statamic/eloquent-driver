<?php

namespace Statamic\Eloquent\Structures;

use Statamic\Eloquent\Structures\TreeModel as Model;
use Statamic\Structures\CollectionTree as FileEntry;

class CollectionTree extends FileEntry
{
    protected $model;

    public static function fromModel(Model $model)
    {
        return (new static)
            ->tree($model->tree)
            ->handle($model->handle)
            ->locale($model->locale)
            ->initialPath($model->initial_path)
            ->syncOriginal()
            ->model($model);
    }

    public function toModel()
    {
        return self::makeModelFromContract($this);
    }

    public static function makeModelFromContract($source)
    {
        $class = app('statamic.eloquent.navigations.tree_model');

        return $class::firstOrNew([
            'handle' => $source->handle(),
            'type' => 'collection',
            'locale' => $source->locale(),
        ])->fill([
            'handle' => $source->handle(),
            'initial_path' => $source->initialPath(),
            'locale' => $source->locale(),
            'tree' => $source->tree(),
            'type' => 'collection',
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
