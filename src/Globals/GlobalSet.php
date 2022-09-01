<?php

namespace Statamic\Eloquent\Globals;

use Statamic\Contracts\Globals\Variables as VariablesContract;
use Statamic\Eloquent\Globals\GlobalSetModel as Model;
use Statamic\Globals\GlobalSet as FileEntry;

class GlobalSet extends FileEntry
{
    protected $model;

    public static function fromModel(Model $model)
    {
        $global = (new static())
            ->handle($model->handle)
            ->title($model->title)
            ->model($model);

        $variablesModel = app('statamic.eloquent.global_sets.variables_model');

        foreach ($model->localizations as $localization) {
            $global->addLocalization(app(VariablesContract::class)::fromModel($variablesModel::make($localization)));
        }

        return $global;
    }

    public function toModel()
    {
        $class = app('statamic.eloquent.global_sets.model');

        $localizations = $this->localizations()->map(fn ($value) => $value->toModel()->toArray());

        return $class::firstOrNew(['handle' => $this->handle()])->fill([
            'title'         => $this->title(),
            'localizations' => $localizations,
        ]);
    }

    public function makeLocalization($site)
    {
        return app(VariablesContract::class)
            ->globalSet($this)
            ->locale($site);
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
