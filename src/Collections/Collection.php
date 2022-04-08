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
            ->title($model->title)
            ->routes($model->settings['routes'] ?? null)
            ->requiresSlugs($model->settings['slugs'] ?? true)
            ->titleFormats($model->settings['title_formats'] ?? null)
            ->mount($model->settings['mount'] ?? null)
            ->dated($model->settings['dated'] ?? null)
            ->ampable($model->settings['ampable'] ?? null)
            ->sites($model->settings['sites'] ?? null)
            ->template($model->settings['template'] ?? null)
            ->layout($model->settings['layout'] ?? null)
            ->cascade($model->settings['inject'] ?? [])
            ->searchIndex($model->settings['search_index'] ?? null)
            ->revisionsEnabled($model->settings['revisions'] ?? false)
            ->defaultPublishState($model->settings['default_status'] ?? true)
            ->structureContents($model->settings['structure'] ?? null)
            ->sortField($model->settings['sort_field'] ?? null)
            ->sortDirection($model->settings['sort_dir'] ?? null)
            ->taxonomies($model->settings['taxonomies'] ?? null)
            ->propagate($model->settings['propagate'] ?? null)
            ->futureDateBehavior($model->settings['future_date_behavior'] ?? null)
            ->pastDateBehavior($model->settings['past_date_behavior'] ?? null)
            ->previewTargets($model->settings['preview_targets'] ?? [])
            ->handle($model->handle)
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
                'slugs' => $this->requiresSlugs(),
                'title_formats' => collect($this->titleFormats())->filter()->values(),
                'mount' => $this->mount,
                'dated' => $this->dated,
                'ampable' => $this->ampable,
                'sites' => $this->sites,
                'template' => $this->template,
                'layout' => $this->layout,
                'inject' => $this->cascade,
                'search_index' => $this->searchIndex,
                'revisions' => $this->revisionsEnabled(),
                'default_status' => $this->defaultPublishState,
                'structure' => $this->structureContents(),
                'sort_dir' => $this->sortDirection(),
                'sort_field' => $this->sortField(),
                'taxonomies' => $this->taxonomies,
                'propagate' => $this->propagate(),
                'past_date_behavior' => $this->pastDateBehavior(),
                'future_date_behavior' => $this->futureDateBehavior(),
                'preview_targets' => $this->previewTargets(),
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
            ->expectsRoot($this->structureContents['root'] ?? false)
            ->showSlugs($this->structureContents['slugs'] ?? false)
            ->maxDepth($this->structureContents['max_depth'] ?? null);
    }
}
