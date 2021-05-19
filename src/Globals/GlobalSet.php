<?php


namespace Statamic\Eloquent\Globals;


use Statamic\Eloquent\Taxonomies\Term;
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

        foreach($model->localizations as $localization) {
            $global->addLocalization(Variables::fromModel(VariablesModel::make($localization)));
        }

        return $global;
    }

    public function toModel()
    {
        $class = app('statamic.eloquent.global-sets.model');

        $localizations = $this->localizations()->map(function($value, $key) {
            return $value->toModel()->toArray();
        });

        return $class::findOrNew($this->model?->id)->fill([
            'handle' => $this->handle(),
            'title' => $this->title(),
            'localizations' => $localizations,
        ]);
    }

    public function makeLocalization($site)
    {
        return (new Variables)
            ->globalSet($this)
            ->locale($site);
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
