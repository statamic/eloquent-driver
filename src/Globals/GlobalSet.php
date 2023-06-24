<?php

namespace Statamic\Eloquent\Globals;

use Statamic\Contracts\Globals\GlobalSet as Contract;
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

        $localizations = $variablesModel::query()->where('handle', $model->handle)->all();
        foreach ($localizations as $localization) {
            $global->addLocalization(app(VariablesContract::class)::fromModel($localization));
        }

        return $global;
    }

    public function toModel()
    {
        return self::makeModelFromContract($this);
    }

    public static function makeModelFromContract(Contract $source)
    {
        $class = app('statamic.eloquent.global_sets.model');

        $source->localizations()->each(function ($value) {
            Variables::makeModelFromContract($value);
        });

        return $class::firstOrNew(['handle' => $source->handle()])->fill([
            'title' => $source->title(),
            'settings'  => [], // future proofing
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
