<?php

namespace Statamic\Eloquent\Structures;

use Illuminate\Database\Eloquent\Model;
use Statamic\Contracts\Structures\Nav as Contract;
use Statamic\Structures\Nav as FileEntry;

class Nav extends FileEntry
{
    protected $model;

    public static function fromModel(Model $model)
    {
        return (new static)
            ->handle($model->handle)
            ->title($model->title)
            ->collections($model->settings['collections'] ?? null)
            ->maxDepth($model->settings['max_depth'] ?? null)
            ->expectsRoot($model->settings['expects_root'] ?? false)
            ->canSelectAcrossSites($model->settings['select_across_sites'] ?? false)
            ->model($model);
    }

    public function newTreeInstance()
    {
        return app(app('statamic.eloquent.navigations.tree'));
    }

    public function toModel()
    {
        return self::makeModelFromContract($this);
    }

    public static function makeModelFromContract(Contract $source)
    {
        $class = app('statamic.eloquent.navigations.model');

        $model = $class::firstOrNew(['handle' => $source->handle()])->fill([
            'title'    => $source->title(),
            'settings' => [],
        ]);

        $model->settings = array_merge($model->settings ?? [], [
            'collections'  => $source->collections()->map->handle(),
            'select_across_sites' => $source->canSelectAcrossSites(),
            'max_depth'    => $source->maxDepth(),
            'expects_root' => $source->expectsRoot(),
        ]);

        return $model;
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
