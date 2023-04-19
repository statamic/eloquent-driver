<?php

namespace Statamic\Eloquent\Collections;

use Statamic\Contracts\Entries\Collection as Contract;
use Statamic\Eloquent\Collections\CollectionModel as Model;
use Statamic\Eloquent\Structures\CollectionStructure;
use Statamic\Entries\Collection as FileEntry;

class Collection extends FileEntry
{
    protected $model;

    public static function fromModel(Model $model)
    {
        return (new static())
            ->title($model->title ?? null)
            ->routes($model->settings['routes'] ?? null)
            ->requiresSlugs($model->settings['slugs'] ?? true)
            ->titleFormats($model->settings['title_formats'] ?? null)
            ->mount($model->settings['mount'] ?? null)
            ->dated($model->settings['dated'] ?? null)
            ->sites($model->settings['sites'] ?? null)
            ->template($model->settings['template'] ?? null)
            ->layout($model->settings['layout'] ?? null)
            ->cascade($model->settings['inject'] ?? [])
            ->searchIndex($model->settings['search_index'] ?? null)
            ->revisionsEnabled($model->settings['revisions'] ?? false)
            ->defaultPublishState($model->settings['default_status'] ?? true)
            ->originBehavior($model->settings['origin_behavior'] ?? 'select')
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
        return self::makeModelFromContract($this);
    }

    public static function makeModelFromContract(Contract $source)
    {
        $class = app('statamic.eloquent.collections.model');

        return $class::firstOrNew(['handle' => $source->handle])->fill([
            'title'    => $source->title ?? $source->handle,
            'settings' => [
                'routes'               => $source->routes,
                'slugs'                => $source->requiresSlugs(),
                'title_formats'        => collect($source->titleFormats())->filter(),
                'mount'                => $source->mount,
                'dated'                => $source->dated,
                'sites'                => $source->sites,
                'template'             => $source->template,
                'layout'               => $source->layout,
                'inject'               => $source->cascade,
                'search_index'         => $source->searchIndex,
                'revisions'            => $source->revisionsEnabled(),
                'default_status'       => $source->defaultPublishState,
                'structure'            => $source->structureContents(),
                'sort_dir'             => $source->sortDirection(),
                'sort_field'           => $source->sortField(),
                'taxonomies'           => $source->taxonomies,
                'propagate'            => $source->propagate(),
                'past_date_behavior'   => $source->pastDateBehavior(),
                'future_date_behavior' => $source->futureDateBehavior(),
                'preview_targets'      => $source->previewTargets(),
                'origin_behavior'      => $source->originBehavior(),
            ],
        ]);
    }

    public function model($model = null)
    {
        if (func_num_args() === 0) {
            return $this->model;
        }

        $this->model = $model;

        if (! is_null($model)) {
            $this->id($model->id);
        }

        return $this;
    }

    protected function makeStructureFromContents()
    {
        return (new CollectionStructure())
            ->handle($this->handle())
            ->expectsRoot($this->structureContents['root'] ?? false)
            ->showSlugs($this->structureContents['slugs'] ?? false)
            ->maxDepth($this->structureContents['max_depth'] ?? null);
    }
}
