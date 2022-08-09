<?php

namespace Statamic\Eloquent\Taxonomies;

use Statamic\Contracts\Taxonomies\Taxonomy as Contract;
use Statamic\Eloquent\Taxonomies\TaxonomyModel as Model;
use Statamic\Taxonomies\Taxonomy as FileEntry;

class Taxonomy extends FileEntry
{
    protected $model;

    public static function fromModel(Model $model)
    {
        return (new static)
            ->handle($model->handle)
            ->title($model->title)
            ->sites($model->sites)
            ->revisionsEnabled($model->settings['revisions'] ?? false)
            ->model($model);
    }

    public function toModel()
    {
        return self::makeModelFromContract($this);
    }

    public static function makeModelFromContract(Contract $source)
    {
        $class = app('statamic.eloquent.taxonomies.model');

        return $class::firstOrNew(['handle' => $source->handle()])->fill([
            'handle' => $source->handle(),
            'title' => $source->title(),
            'sites' => $source->sites(),
            'settings' => [
                'revisions' => $source->revisionsEnabled(),
            ],
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
