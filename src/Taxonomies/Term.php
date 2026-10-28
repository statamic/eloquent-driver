<?php

namespace Statamic\Eloquent\Taxonomies;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Statamic\Contracts\Taxonomies\Term as Contract;
use Statamic\Facades\Blink;
use Statamic\Taxonomies\Term as FileEntry;

class Term extends FileEntry
{
    protected $model;

    public static function fromModel(Model $model)
    {
        $modelClass = app('statamic.eloquent.terms.model');

        // Locale rows preloaded by TermQueryBuilder::transform() are cached in
        // Blink to avoid N+1. Fall back to a direct DB query when not present
        // (e.g. when fromModel() is called outside of a query builder context).
        $blinkKey = "eloquent-term-locale-rows-{$model->taxonomy}::{$model->slug}";
        $allRows = Blink::has($blinkKey)
            ? Blink::get($blinkKey)
            : $modelClass::where('slug', $model->slug)->where('taxonomy', $model->taxonomy)->get();

        // Non-canonical rows (origin set) don't carry term-level attributes like
        // blueprint or collection, so we always read those from the canonical row.
        $canonicalModel = $model->origin
            ? ($allRows->firstWhere('origin', null) ?? $modelClass::find($model->origin) ?? $model)
            : $model;

        $data = $canonicalModel->data;

        $term = (new static)
            ->slug($model->slug)
            ->taxonomy($model->taxonomy)
            ->model($canonicalModel)
            ->blueprint($data['blueprint'] ?? null);

        $allRows->each(function ($localeModel) use ($term) {
            $term->dataForLocale($localeModel->site, $localeModel->data ?? []);
        });

        // Backward compat: terms saved before the per-row migration may still
        // carry localizations embedded in the data JSON column.
        collect($data['localizations'] ?? [])
            ->each(function ($localeData, $locale) use ($term) {
                $term->dataForLocale($locale, $localeData);
            });

        if (isset($data['collection'])) {
            $term->collection($data['collection']);
        }

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

        // Localizations are now stored as separate rows; remove from the data column.
        unset($data['localizations']);

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
            'origin'     => null,
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
