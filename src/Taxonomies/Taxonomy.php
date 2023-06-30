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
        return (new static())
            ->handle($model->handle)
            ->title($model->title)
            ->sites($model->sites)
            ->revisionsEnabled($model->settings['revisions'] ?? false)
            ->previewTargets($model->settings['preview_targets'] ?? [])
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
            'title'    => $source->title(),
            'sites'    => $source->sites(),
            'settings' => [
                'revisions' => $source->revisionsEnabled(),
                 'preview_targets' => $source->previewTargets(),
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
}
