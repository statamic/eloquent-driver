<?php

namespace Statamic\Eloquent\Taxonomies;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Statamic\Contracts\Taxonomies\Term as TermContract;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Query\EloquentQueryBuilder;
use Statamic\Taxonomies\TermCollection;

class TermQueryBuilder extends EloquentQueryBuilder
{
    protected $collections = [];

    protected $site = null;

    protected $taxonomies = [];

    protected $columns = [
        'id', 'data', 'site', 'slug', 'uri', 'taxonomy', 'created_at', 'updated_at',
    ];

    protected function transform($items, $columns = [])
    {
        $site = $this->site;
        if (! $site) {
            $site = Site::default()->handle();
        }

        return TermCollection::make($items)->map(function ($model) use ($site) {
            return app(TermContract::class)::fromModel($model)->in($site);
        });
    }

    protected function column($column)
    {
        if (! is_string($column)) {
            return $column;
        }

        if (! in_array($column, $this->columns)) {
            if (! Str::startsWith($column, 'data->')) {
                $column = 'data->'.$column;
            }
        }

        return $column;
    }

    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        if ($column === 'site') {
            $this->site = $operator;

            return $this;
        }

        if (func_num_args() === 2) {
            [$value, $operator] = [$operator, '='];
        }

        if (in_array($column, ['taxonomy', 'taxonomies'])) {
            if (! $value) {
                return $this;
            }

            if (! is_array($value)) {
                $value = [$value];
            }

            $this->taxonomies = array_merge($this->taxonomies, $value);

            return $this;
        }

        if (in_array($column, ['collection', 'collections'])) {
            if (! $value) {
                return $this;
            }

            if (! is_array($value)) {
                $value = [$value];
            }

            $this->collections = array_merge($this->collections, $value);

            return $this;
        }

        if (in_array($column, ['id', 'slug'])) {
            $column = 'slug';

            if (str_contains($value, '::')) {

                $taxonomy = Str::before($value.'', '::');

                if ($taxonomy) {
                    $this->taxonomies[] = $taxonomy;
                }

                $value = Str::after($value, '::');

            }
        }

        parent::where($column, $operator, $value, $boolean);

        return $this;
    }

    public function whereIn($column, $values, $boolean = 'and')
    {
        if (in_array($column, ['taxonomy', 'taxonomies'])) {
            if (! $values) {
                return $this;
            }

            $this->taxonomies = array_merge($this->taxonomies, collect($values)->all());

            return $this;
        }

        if (in_array($column, ['collection', 'collections'])) {
            if (! $values) {
                return $this;
            }

            $this->collections = array_merge($this->collections, collect($values)->all());

            return $this;
        }

        if (in_array($column, ['id', 'slug'])) {
            $column = 'slug';
            $values = collect($values)
                ->map(function ($value) {
                    return Str::after($value, '::');
                })
                ->all();
        }

        parent::whereIn($column, $values, $boolean);

        return $this;
    }

    public function get($columns = ['*'])
    {
        $this->applyCollectionAndTaxonomyWheres();

        $items = parent::get($columns);

        // If a single collection has been queried, we'll supply it to the terms so
        // things like URLs will be scoped to the collection. We can't do it when
        // multiple collections are queried because it would be ambiguous.
        if ($this->collections && count($this->collections) == 1) {
            $items->each->collection(Collection::findByHandle($this->collections[0]));
        }

        return $items->map(function ($term) {
            if ($this->site) {
                return $term->in($this->site);
            }

            return $term->inDefaultLocale();
        });
    }

    public function count()
    {
        $this->applyCollectionAndTaxonomyWheres();

        return parent::count();
    }

    public function paginate($perPage = null, $columns = [], $pageName = 'page', $page = null)
    {
        $this->applyCollectionAndTaxonomyWheres();

        return parent::paginate($perPage = null, $columns = [], $pageName = 'page', $page = null);
    }

    private function applyCollectionAndTaxonomyWheres()
    {
        if (! empty($this->collections)) {
            $taxonomies = empty($this->taxonomies)
                ? Facades\Taxonomy::handles()
                : $this->taxonomies;

            // get entries in each collection that have a value for the taxonomies we are querying
            // or the ones associated with the collection
            // what we ultimately want is a subquery for terms in the form:
            // where('taxonomy', $taxonomy)->whereIn('slug', $slugArray)
            Entry::whereInCollection($this->collections)
                ->get($taxonomies)
                ->flatMap(function ($entry) use ($taxonomies) {
                    $slugs = [];
                    foreach ($entry->collection()->taxonomies()->map->handle() as $taxonomy) {
                        if (in_array($taxonomy, $taxonomies)) {
                            foreach ($entry->get($taxonomy, []) as $term) {
                                $slugs[] = $taxonomy.'::'.$term;
                            }
                        }
                    }

                    return $slugs;
                })
                ->unique()
                ->map(function ($term) {
                    return [
                        'taxonomy' => Str::before($term, '::'),
                        'term' => Str::after($term, '::'),
                    ];
                })
                ->mapIntoGroups(function ($value) {
                    return [$item['taxonomy'] => $item['term']];
                })
                ->each(function ($terms, $taxonomy) {
                    $this->builder->where(function ($query) use ($terms, $taxonomy) {
                        $query->where('taxonomy', $taxonomy)
                            ->whereIn('slug', $terms);
                    });
                });
        }

        if (! empty($this->taxonomies)) {
            $queryTaxonomies = collect($this->taxonomies)
                ->filter()
                ->unique();

            if ($queryTaxonomies->count() > 0) {
                $this->builder->whereIn('taxonomy', $queryTaxonomies->all());
            }
        }
    }
}
