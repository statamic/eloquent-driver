<?php

namespace Statamic\Eloquent\Taxonomies;

use Statamic\Eloquent\Taxonomies\TaxonomyModel as Model;
use Statamic\Taxonomies\Taxonomy  as FileEntry;

class Taxonomy extends FileEntry
{
    protected $model;

    public static function fromModel(Model $model)
    {
        return (new static)
            ->handle($model->handle)
            ->title($model->title)
            ->sites($model->sites)
            ->model($model);
    }

    public function toModel()
    {
        $class = app('statamic.eloquent.taxonomies.model');

        return $class::findOrNew($this->model?->id)->fill([
            'handle' => $this->handle(),
            'title' => $this->title(),
            'sites' => $this->sites(),
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
