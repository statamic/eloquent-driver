<?php

namespace Statamic\Eloquent\Sites;

use Illuminate\Support\Facades\Schema;
use Statamic\Support\Str;

class Sites extends \Statamic\Sites\Sites
{
    protected function getSavedSites()
    {
        $class = app('statamic.eloquent.sites.model');

        if (! Schema::hasTable((new $class)->getTable())) {
            return $this->getFallbackConfig();
        }

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
