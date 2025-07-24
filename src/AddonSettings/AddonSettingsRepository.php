<?php

namespace Statamic\Eloquent\AddonSettings;

use Statamic\Addons\SettingsRepository as FileSettingsRepository;
use Statamic\Contracts\Addons\Settings as AddonSettingsContract;

class AddonSettingsRepository extends FileSettingsRepository
{
    public function find(string $addon): ?AddonSettingsContract
    {
        $model = app('statamic.eloquent.addon_settings.model')::find($addon);

        if (! $model) {
            return null;
        }

        return AddonSettings::fromModel($model);
    }

    public function save(AddonSettingsContract $settings): bool
    {
        return $settings->toModel()->save();
    }

    public function delete(AddonSettingsContract $settings): bool
    {
        return $settings->toModel()->delete();
    }

    public static function bindings(): array
    {
        return [
            AddonSettingsContract::class => AddonSettings::class,
        ];
    }
}
