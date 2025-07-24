<?php

namespace Statamic\Eloquent\AddonSettings;

use Illuminate\Database\Eloquent\Model;
use Statamic\Addons\Settings as AbstractSettings;
use Statamic\Facades\Addon;

class AddonSettings extends AbstractSettings
{
    protected $model;

    public static function fromModel(Model $model)
    {
        $addon = Addon::get($model->addon);

        return (new static($addon, $model->settings))->model($model);
    }

    public function toModel()
    {
        return self::makeModelFromContract($this);
    }

    public static function makeModelFromContract(AbstractSettings $settings)
    {
        $class = app('statamic.eloquent.addon_settings.model');

        return $class::firstOrNew(['addon' => $settings->addon()->id()])->fill([
            'settings' => array_filter($settings->raw()),
        ]);
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
