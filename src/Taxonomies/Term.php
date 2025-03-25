<?php

namespace Statamic\Eloquent\Taxonomies;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Statamic\Contracts\Taxonomies\Term as Contract;
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

        if (config('statamic.system.track_last_update')) {
            $updatedAt = ($model->updated_at ?? $model->created_at);

            $term->set('updated_at', $updatedAt instanceof Carbon ? $updatedAt->timestamp : $updatedAt);
        }

        return $term->syncOriginal();
    }

    public function toModel()
    {
        return self::makeModelFromContract($this);
    }

    public static function makeModelFromContract(Contract $source)
    {
        $class = app('statamic.eloquent.terms.model');

        $data = $source->fileData();

        if (! isset($data['template'])) {
            unset($data['template']);
        }

        if ($source->blueprint && $source->taxonomy()->termBlueprints()->count() > 1) {
            $data['blueprint'] = $source->blueprint;
        }

        $source->localizations()->keys()->reduce(function ($data, $locale) use ($source) {
            $data[$locale] = $source->dataForLocale($locale)->toArray();

            return $data;
        }, []);

        if ($collection = $source->collection()) {
            $data['collection'] = $collection;
        }

        return $class::firstOrNew([
            'slug'     => $source->getOriginal('slug', $source->slug()),
            'taxonomy' => $source->taxonomy(),
            'site'     => $source->locale(),
        ])->fill([
            'slug'       => $source->slug(),
            'uri'        => $source->uri(),
            'data'       => collect($data)->filter(fn ($v) => $v !== null),
            'updated_at' => $source->lastModified(),
        ]);
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

    public function fileLastModified()
    {
        return $this->model?->updated_at ?? Carbon::now();
    }
}
