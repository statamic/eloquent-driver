<?php

namespace Statamic\Eloquent\Entries;

use Illuminate\Support\Str;
use Statamic\Contracts\Entries\QueryBuilder;
use Statamic\Entries\EntryCollection;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Query\EloquentQueryBuilder;
use Statamic\Stache\Query\QueriesTaxonomizedEntries;

class EntryQueryBuilder extends EloquentQueryBuilder implements QueryBuilder
{
    use QueriesTaxonomizedEntries;

    private $selectedQueryColumns;

    private const STATUSES = ['published', 'draft', 'scheduled', 'expired'];

    const COLUMNS = [
        'id', 'site', 'origin_id', 'published', 'slug', 'uri',
        'date', 'collection', 'created_at', 'updated_at', 'order', 'blueprint',
    ];

    public function orderBy($column, $direction = 'asc')
    {
        $actualColumn = $this->column($column);

        if (Str::contains($actualColumn, 'data->')) {
            $wheres = collect($this->builder->getQuery()->wheres);

            if ($wheres->where('column', 'collection')->count() == 1) {
                $collectionWhere = $wheres->firstWhere('column', 'collection');
                if (isset($collectionWhere['values']) && count($collectionWhere['values']) == 1) {
                    $collectionWhere['value'] = $collectionWhere['values'][0];
                }

                if (isset($collectionWhere['value'])) {
                    if ($collection = Collection::find($collectionWhere['value'])) {
                        $blueprintField = $collection->entryBlueprints()
                            ->flatMap(function ($blueprint) {
                                return $blueprint->fields()
                                    ->all()
                                    ->filter(function ($field) {
                                        return in_array($field->type(), ['float', 'integer', 'date']);
                                    });
                            })
                            ->filter()
                            ->get($column);

                        if ($blueprintField) {
                            $castType = '';
                            $fieldType = $blueprintField->type();

                            $grammar = $this->builder->getConnection()->getQueryGrammar();
                            $actualColumn = $grammar->wrap($actualColumn);

                            if (in_array($fieldType, ['float'])) {
                                $castType = 'float';
                            } elseif (in_array($fieldType, ['integer'])) {
                                $castType = 'float'; // bit sneaky but mysql doesnt support casting as integer, it wants unsigned
                            } elseif (in_array($fieldType, ['date'])) {
                                $castType = 'date';

                                // sqlite casts dates to year, which is pretty unhelpful
                                if (str_contains(get_class($grammar), 'SQLiteGrammar')) {
                                    $this->builder->orderByRaw("datetime({$actualColumn}) {$direction}");

                                    return $this;
                                }
                            }

                            if ($castType) {
                                $this->builder->orderByRaw("cast({$actualColumn} as {$castType}) {$direction}");

                                return $this;
                            }
                        }
                    }
                }
            }
        }

        parent::orderBy($column, $direction);

        return $this;
    }

    protected function transform($items, $columns = [])
    {
        $items = EntryCollection::make($items)->map(function ($model) use ($columns) {
            return app('statamic.eloquent.entries.entry')::fromModel($model)
                ->selectedQueryColumns($this->selectedQueryColumns ?? $columns);
        });

        return Entry::applySubstitutions($items);
    }

    protected function column($column)
    {
        if (! is_string($column)) {
            return $column;
        }

        $table = Str::contains($column, '.') ? Str::before($column, '.') : '';
        $column = Str::after($column, '.');

        if ($column == 'origin') {
            $column = 'origin_id';
        }

        if (! in_array($column, self::COLUMNS)) {
            if (! Str::startsWith($column, 'data->')) {
                $column = 'data->'.$column;
            }
        }

        return ($table ? $table.'.' : '').$column;
    }

    public function find($id, $columns = ['*'])
    {
        $model = parent::find($id, $columns);

        if ($model) {
            return app('statamic.eloquent.entries.entry')::fromModel($model)
                ->selectedQueryColumns($columns);
        }
    }

    public function get($columns = ['*'])
    {
        $query = $this->builder->getQuery();
        if ($query->offset && ! $query->limit) {
            $query->limit = PHP_INT_MAX;
        }

        $this->selectedQueryColumns = $columns;

        $this->addTaxonomyWheres();

        return parent::get();
    }

    public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        $this->addTaxonomyWheres();

        $this->selectedQueryColumns = $columns;

        return parent::paginate($perPage, ['*'], $pageName, $page);
    }

    public function count()
    {
        $this->addTaxonomyWheres();

        return parent::count();
    }

    public function with($relations, $callback = null)
    {
        return $this;
    }

    public function whereStatus(string $status)
    {
        if (! in_array($status, self::STATUSES)) {
            throw new \Exception("Invalid status [$status]");
        }

        if ($status === 'draft') {
            return $this->where('published', false);
        }

        $this->where('published', true);

        return $this->where(fn ($query) => $this
            ->getCollectionsForStatus()
            ->each(fn ($collection) => $query->orWhere(fn ($q) => $this->addCollectionStatusLogicToQuery($q, $status, $collection))));
    }

    private function getCollectionsForStatus()
    {
        // Since we have to add nested queries for each collection, if collections have been provided,
        // we'll use those to avoid the need for adding unnecessary query clauses.

        $wheres = collect($this->builder->getQuery()->wheres);

        if ($wheres->where('column', 'collection')->isEmpty()) {
            return Collection::all();
        }

        return $wheres->where('column', 'collection')->pluck('value')->map(fn ($handle) => Collection::find($handle));
    }

    private function addCollectionStatusLogicToQuery($query, $status, $collection)
    {
        $query->where('collection', $collection->handle());

        if ($collection->futureDateBehavior() === 'public' && $collection->pastDateBehavior() === 'public') {
            if ($status === 'scheduled' || $status === 'expired') {
                $query->where('date', 'invalid'); // intentionally trigger no results.
            }
        }

        if ($collection->futureDateBehavior() === 'private') {
            $status === 'scheduled'
                ? $query->where('date', '>', now())
                : $query->where('date', '<', now());

            if ($status === 'expired') {
                $query->where('date', 'invalid'); // intentionally trigger no results.
            }
        }

        if ($collection->pastDateBehavior() === 'private') {
            $status === 'expired'
                ? $query->where('date', '<', now())
                : $query->where('date', '>', now());

            if ($status === 'scheduled') {
                $query->where('date', 'invalid'); // intentionally trigger no results.
            }
        }
    }
}
