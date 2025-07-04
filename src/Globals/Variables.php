<?php

namespace Statamic\Eloquent\Globals;

use Illuminate\Database\Eloquent\Model;
use Statamic\Contracts\Globals\Variables as Contract;
use Statamic\Globals\Variables as FileEntry;

class Variables extends FileEntry
{
    protected $model;

    public static function fromModel(Model $model)
    {
        return (new static)
            ->globalSet($model->handle)
            ->locale($model->locale)
            ->data($model->data)
            ->origin($model->origin ?? null)
            ->model($model);
    }

    public function toModel()
    {
        return self::makeModelFromContract($this);
    }

    public static function makeModelFromContract(Contract $source)
    {
        $class = app('statamic.eloquent.global_set_variables.model');

        $data = $source->data();

        if ($source->hasOrigin()) {
            $data = $source->origin()->data()->merge($data);
        }

        return $class::firstOrNew([
            'handle' => $source->globalSet()->handle(),
            'locale' => $source->locale,
        ])->fill([
            'data'   => $data->filter(fn ($v) => $v !== null),
            'origin' => $source->hasOrigin() ? $source->origin()->locale() : null,
        ]);
    }

    protected function getOriginByString($origin)
    {
        return $this->globalSet()->in($origin);
    }

    public function model($model = null)
    {
        if (func_num_args() === 0) {
            return $this->model;
        }

        $this->model = $model;

        return $this;
    }
}
