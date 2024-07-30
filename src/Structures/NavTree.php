<?php

namespace Statamic\Eloquent\Structures;

use Illuminate\Database\Eloquent\Model;
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

        $isFileEntry = get_class($source) == FileEntry::class;

        return $class::firstOrNew([
            'handle' => $source->handle(),
            'type'   => 'navigation',
            'locale' => $source->locale(),
        ])->fill([
            'tree'     => ($isFileEntry || $source->model) ? $source->tree() : [],
            'settings' => [],
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
