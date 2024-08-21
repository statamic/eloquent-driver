<?php

namespace Statamic\Eloquent\Globals;

use Statamic\Contracts\Globals\GlobalSet as Contract;
use Statamic\Eloquent\Globals\GlobalSetModel as Model;
use Statamic\Globals\GlobalSet as FileEntry;

class GlobalSet extends FileEntry
{
    protected $model;

    public static function fromModel(Model $model)
    {
        $global = (new static)
            ->handle($model->handle)
            ->title($model->title)
            ->model($model);

        return $global;
    }

    public function toModel()
    {
        return self::makeModelFromContract($this);
    }

    public static function makeModelFromContract(Contract $source)
    {
        $class = app('statamic.eloquent.global_sets.model');

        return $class::firstOrNew(['handle' => $source->handle()])->fill([
            'title' => $source->title(),
            'settings'  => [], // future proofing
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
