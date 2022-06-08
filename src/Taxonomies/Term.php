<?php

namespace Statamic\Eloquent\Taxonomies;

use Statamic\Eloquent\Taxonomies\TermModel as Model;
use Statamic\Taxonomies\Term as FileEntry;

class Term extends FileEntry
{
    protected $model;

    public static function fromModel(Model $model)
    {
        $data = $model->data;

        /** @var Term $term */
        $term = (new static)
            ->slug($model->slug)
            ->taxonomy($model->taxonomy)
            ->model($model)
            ->blueprint($model->data['blueprint'] ?? null);

        collect($data['localizations'] ?? [])
            ->except($term->defaultLocale())
            ->each(function ($localeData, $locale) use ($term) {
                $term->dataForLocale($locale, $localeData);
            });

        unset($data['localizations']);

        if (isset($data['collection'])) {
            $term->collection($data['collection']);
            unset($data['collection']);
        }

        $term->data($data);

        return $term;
    }

    public function toModel()
    {
        $class = app('statamic.eloquent.taxonomies.term_model');

        $data = $this->data();

        if (isset($data['template']) && is_null($data['template'])) {
            unset($data['template']);
        }

        if ($this->blueprint && $this->taxonomy()->termBlueprints()->count() > 1) {
            $data['blueprint'] = $this->blueprint;
        }

        $data['localizations'] = $this->localizations()->keys()->reduce(function ($localizations, $locale) {
            $localizations[$locale] = $this->dataForLocale($locale)->toArray();

            return $localizations;
        }, []);

        if ($collection = $this->collection()) {
            $data['collection'] = $collection;
        }

        return $class::findOrNew($this->model?->id)->fill([
            'site' => $this->locale(),
            'slug' => $this->slug(),
            'uri' => $this->uri(),
            'taxonomy' => $this->taxonomy(),
            'data' => $data,
        ]);
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

    public function lastModified()
    {
        return $this->model?->updated_at;
    }
}
