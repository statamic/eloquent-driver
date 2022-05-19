<?php

namespace Statamic\Eloquent\Taxonomies;

use Illuminate\Support\Str;
use Statamic\Contracts\Taxonomies\Term as TermContract;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;
use Statamic\Query\EloquentQueryBuilder;
use Statamic\Taxonomies\TermCollection;

class TermQueryBuilder extends EloquentQueryBuilder
{
    protected $collections = [];
    protected $site = null;
    protected $taxonomies = [];
    protected $query;

    protected $columns = [
        'id', 'data', 'site', 'slug', 'uri', 'taxonomy', 'created_at', 'updated_at',
    ];

    protected function transform($items, $columns = [])
    {
        $site = $this->site;
        if(!$site) {
            $site = Site::default()->handle();
        }

        return TermCollection::make($items)->map(function ($model) use($site) {
            return app(TermContract::class)::fromModel($model)->in($site);
        });
    }

    protected function column($column)
    {
        if (! is_string($column)) {
            return $column;
        }

        if (! in_array($column, $this->columns)) {
            $column = 'data->'.$column;
        }

        return $column;
    }


    public function get($columns = ['*'])
    {
        if (! empty($this->collections)) {
            $collectionTaxonomies = collect($this->collections)
                ->map(function ($handle) {
                    return Collection::findByHandle($handle)?->taxonomies() ?? [];
                })
                ->flatten()
                ->filter()
                ->unique();

            $this->taxonomies = array_merge($this->taxonomies, $collectionTaxonomies->all());
        }

        $queryTaxonomies = collect($this->taxonomies)
            ->filter()
            ->unique();

        if ($queryTaxonomies->count() > 0) {
            $this->builder->whereIn('taxonomy', $queryTaxonomies->all());
        }

        

        $items = parent::get($columns);

        // If a single collection has been queried, we'll supply it to the terms so
        // things like URLs will be scoped to the collection. We can't do it when
        // multiple collections are queried because it would be ambiguous.
        if ($this->collections && count($this->collections) == 1) {
            $items->each->collection(Collection::findByHandle($this->collections[0]));
        }

        return $items;
    }
}
