<?php

namespace Statamic\Eloquent\Sites;

use Statamic\Support\Str;
use Illuminate\Support\Facades\Schema;

class Sites extends \Statamic\Sites\Sites
{
    protected function getSavedSites()
    {
        $sitesTable = config('statamic.eloquent-driver.table_prefix', '').'entries';
        if(! Schema::hasTable($sitesTable)) {
            return $this->getFallbackConfig();
        };

        $sites = app('statamic.eloquent.sites.model')::all();

        return $sites->isEmpty() ? $this->getFallbackConfig() : $sites->mapWithKeys(function ($model) {
            return [
                $model->handle => [
                    'name' => $model->name,
                    'lang' => $model->lang,
                    'locale' => $model->locale,
                    'url' => $model->url,
                    'attributes' => $model->attributes ?? [],
                ],
            ];
        });
    }

    protected function saveToStore()
    {
        foreach ($this->config() as $handle => $config) {
            $lang = $config['lang'] ?? Str::before($config['locale'] ?? '', '_') ?? 'en';

            app('statamic.eloquent.sites.model')::firstOrNew(['handle' => $handle])
                ->fill([
                    'name' => $config['name'] ?? '',
                    'lang' => $lang,
                    'locale' => $config['locale'] ?? '',
                    'url' => $config['url'] ?? '',
                    'attributes' => $config['attributes'] ?? [],
                ])
                ->save();
        }

        app('statamic.eloquent.sites.model')::whereNotIn('handle', array_keys($this->config()))->get()->each->delete();
    }
}
