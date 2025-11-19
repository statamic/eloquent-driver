<?php

namespace Statamic\Eloquent\Taxonomies;

use Illuminate\Database\Eloquent\Model;
use Statamic\Contracts\Taxonomies\Taxonomy as Contract;
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
            ->cascade($model->settings['inject'] ?? [])
            ->revisionsEnabled($model->settings['revisions'] ?? false)
            ->previewTargets($model->settings['preview_targets'] ?? [])
            ->searchIndex($model->settings['search_index'] ?? '')
            ->termTemplate($model->settings['term_template'] ?? null)
            ->template($model->settings['template'] ?? null)
            ->layout($model->settings['layout'] ?? null)
            ->model($model);
    }

    public function toModel()
    {
        return self::makeModelFromContract($this);
    }

    public static function makeModelFromContract(Contract $source)
    {
        $class = app('statamic.eloquent.taxonomies.model');

        $model = $class::firstOrNew(['handle' => $source->handle()])->fill([
            'title' => $source->title(),
            'sites' => $source->sites(),
            'settings' => [],
        ]);

        $model->settings = array_merge($model->settings ?? [], [
            'inject' => $source->cascade->all(),
            'revisions' => $source->revisionsEnabled(),
            'preview_targets' => $source->previewTargets(),
            'term_template' => $source->hasCustomTermTemplate() ? $source->termTemplate() : null,
            'template' => $source->hasCustomTemplate() ? $source->template() : null,
            'layout' => $source->layout,
        ]);

        return $model;
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
}
