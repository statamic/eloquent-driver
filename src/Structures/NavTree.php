<?php


namespace Statamic\Eloquent\Structures;

use Statamic\Structures\NavTree as FileEntry;
use Statamic\Eloquent\Structures\NavTreeModel as Model;

class NavTree extends FileEntry
{
    protected $model;

    public static function fromModel(Model $model)
    {
        return (new static)
            ->tree($model->tree)
            ->handle($model->handle)
            ->locale($model->locale)
            ->initialPath($model->initialPath)
            ->model($model);
    }

    public function toModel()
    {
        $class = app('statamic.eloquent.nav-trees.model');

        return $class::findOrNew($this->model?->id)->fill([
            'handle' => $this->handle(),
            'initialPath' => $this->initialPath(),
            'locale' => $this->locale(),
            'tree' => $this->tree,
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
