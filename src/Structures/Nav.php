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
        return (new static())
            ->handle($model->handle)
            ->title($model->title)
            ->collections($model->settings['collections'] ?? null)
            ->maxDepth($model->settings['max_depth'] ?? null)
            ->expectsRoot($model->settings['expects_root'] ?? false)
            ->initialPath($model->settings['initial_path'] ?? null)
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

        return $class::firstOrNew(['handle' => $source->handle()])->fill([
            'title'    => $source->title(),
            'settings' => [
                'collections'  => $source->collections()->map->handle(),
                'max_depth'    => $source->maxDepth(),
                'expects_root' => $source->expectsRoot(),
                'initial_path' => $source->initialPath(),
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
