<?php

namespace Statamic\Eloquent\Sites;

class Sites extends \Statamic\Sites\Sites
{
    protected function getSavedSites()
    {
        $sites = SiteModel::all();

        return $sites->isEmpty() ? $this->getDefaultSite() : $sites->mapWithKeys(function ($model) {
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
            SiteModel::firstOrNew(['handle' => $handle])
                ->fill([
                    'name' => $config['name'] ?? '',
                    'lang' => $config['lang'] ?? '',
                    'locale' => $config['locale'] ?? '',
                    'url' => $config['url'] ?? '',
                    'attributes' => $config['attributes'] ?? [],
                ])
                ->save();
        }

        SiteModel::whereNotIn('handle', array_keys($this->config()))->get()->each->delete();
    }
}
