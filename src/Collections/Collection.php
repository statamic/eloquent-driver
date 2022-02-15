<?php

namespace Statamic\Eloquent\Collections;

use Statamic\Eloquent\Collections\CollectionModel as Model;
use Statamic\Eloquent\Structures\CollectionStructure;
use Statamic\Entries\Collection as FileEntry;

class Collection extends FileEntry
{
    protected $model;

    public static function fromModel(Model $model)
    {
        return (new static)
            ->structureContents($model->settings['structure'] ?? [])
            ->sortDirection($model->settings['sort_dir'] ?? null)
            ->sortField($model->settings['sort_field'] ?? null)
            ->layout($model->settings['layout'] ?? null)
            ->template($model->settings['template'] ?? null)
            ->sites($model->settings['sites'] ?? null)
            ->futureDateBehavior($model->settings['future_date_behavior'] ?? null)
            ->pastDateBehavior($model->settings['past_date_behavior'] ?? null)
            ->ampable($model->settings['ampable'] ?? null)
            ->dated($model->settings['dated'] ?? null)
            ->title($model->title)
            ->handle($model->handle)
            ->routes($model->settings['routes'] ?? null)
            ->taxonomies($model->settings['taxonomies'] ?? null)
            ->mount($model->settings['mount'] ?? null)
            ->titleFormats($model->settings['title_formats'] ?? null)
            ->model($model);
    }

    public function toModel()
    {
        $class = app('statamic.eloquent.collections.model');

        return $class::findOrNew($this->model?->id)->fill([
            'title' => $this->title,
            'handle' => $this->handle,
            'settings' => [
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
                'title_formats' => collect($this->titleFormats())->filter()->values(),
            ]
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

    protected function makeStructureFromContents()
    {
        return (new CollectionStructure)
            ->handle($this->handle())
            ->expectsRoot($this->structureContents->root ?? false)
            ->maxDepth($this->structureContents->max_depth ?? null);
    }
}
