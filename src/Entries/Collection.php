<?php

namespace Statamic\Eloquent\Entries;

use Statamic\Eloquent\Entries\CollectionModel as Model;
use Statamic\Entries\Collection as FileEntry;

class Collection extends FileEntry
{
    protected $model;

    public static function fromModel(Model $model)
    {
        return (new static)
            ->structureContents($model->structure)
            ->sortDirection($model->sort_dir)
            ->sortField($model->sort_field)
            ->layout($model->layout)
            ->template($model->template)
            ->sites($model->sites)
            ->futureDateBehavior($model->future_date_behavior)
            ->pastDateBehavior($model->past_date_behavior)
            ->ampable($model->ampable)
            ->dated($model->dated)
            ->title($model->title)
            ->handle($model->handle)
            ->routes($model->routes)
            ->taxonomies($model->taxonomies)
            ->mount($model->mount)
            ->model($model);
    }

    public function toModel()
    {
        $class = app('statamic.eloquent.collections.model');

        return $class::findOrNew($this->model?->id)->fill([
            'title' => $this->title,
            'handle' => $this->handle,
            'routes' => $this->routes,
            'dated' => $this->dated,
            'past_date_behavior' => $this->pastDateBehavior(),
            'future_date_behavior' => $this->futureDateBehavior(),
            'default_publish_state' => $this->defaultPublishState,
            'ampable' => $this->ampable,
            'sites' => $this->sites,
            'template' => $this->template,
            'layout' => $this->layout,
            'sort_dir' => $this->sortDirection(),
            'sort_field' => $this->sortField(),
            'mount' => $this->mount,
            'taxonomies' => $this->taxonomies,
            'revisions' => $this->revisions,
            'inject' => $this->cascade,
            'structure' => $this->hasStructure() ? $this->structureContents() : null,
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
