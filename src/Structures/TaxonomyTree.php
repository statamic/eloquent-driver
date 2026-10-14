<?php

namespace Statamic\Eloquent\Structures;

use Illuminate\Database\Eloquent\Model;
use Statamic\Facades\Site;
use Statamic\Structures\TaxonomyTree as FileEntry;

class TaxonomyTree extends FileEntry
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
        $class = app('statamic.eloquent.taxonomies.tree_model');

        return $class::firstOrNew([
            'handle' => $source->handle(),
            'type'   => 'taxonomy',
            'locale' => Site::default()->handle(),
        ])->fill([
            'tree'     => $source->tree(),
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
