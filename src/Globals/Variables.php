<?php

namespace Statamic\Eloquent\Globals;

use Statamic\Eloquent\Globals\VariablesModel as Model;
use Statamic\Globals\Variables as FileEntry;

class Variables extends FileEntry
{
    public static function fromModel(Model $model)
    {
        return (new static)
            ->locale($model->locale)
            ->data($model->data)
            ->origin($model->origin);
    }

    public function toModel()
    {
        $class = app('statamic.eloquent.global_sets.variables_model');

        $data = $this->data();

        return $class::make([
            'locale' => $this->locale,
            'data' => $data,
            'origin' => $this->origin,
        ]);
    }
}
