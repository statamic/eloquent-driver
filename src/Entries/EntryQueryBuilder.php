<?php

namespace Statamic\Eloquent\Entries;

use Illuminate\Support\Str;
use Statamic\Contracts\Entries\QueryBuilder;
use Statamic\Entries\EntryCollection;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Query\EloquentQueryBuilder;
use Statamic\Stache\Query\QueriesEntryStatus;
use Statamic\Stache\Query\QueriesTaxonomizedEntries;

class EntryQueryBuilder extends EloquentQueryBuilder implements QueryBuilder
{
    use QueriesEntryStatus,
        QueriesTaxonomizedEntries;

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

    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        if ($column === 'status') {
            trigger_error('Filtering by status is deprecated. Use whereStatus() instead.', E_USER_DEPRECATED);
        }

        return parent::where($column, $operator, $value, $boolean);
    }

    public function whereIn($column, $values, $boolean = 'and')
    {
        if ($column === 'status') {
            trigger_error('Filtering by status is deprecated. Use whereStatus() instead.', E_USER_DEPRECATED);
        }

        return parent::whereIn($column, $values, $boolean);
    }

    private function ensureCollectionsAreQueriedForStatusQuery(): void
    {
        $wheres = collect($this->builder->getQuery()->wheres);

        // If there are where clauses on the collection column, it means the user has explicitly
        // queried for them. In that case, we'll use them and skip the auto-detection.
        if ($wheres->where('column', 'collection')->isNotEmpty()) {
            return;
        }

        // Otherwise, we'll detect them by looking at where clauses targeting the "id" column.
        $ids = $wheres->where('column', 'id')->flatMap(fn ($where) => $where['values'] ?? [$where['value']]);

        // If no IDs were queried, fall back to all collections.
        $ids->isEmpty()
            ? $this->whereIn('collection', Collection::handles())
            : $this->whereIn('collection', app(static::class)->whereIn('id', $ids)->pluck('collection')->unique()->values());
    }

    private function getCollectionsForStatusQuery(): \Illuminate\Support\Collection
    {
        // Since we have to add nested queries for each collection, we only want to add clauses for the
        // applicable collections. By this point, there should be where clauses on the collection column.

        return collect($this->builder->getQuery()->wheres)
            ->where('column', 'collection')
            ->flatMap(fn ($where) => $where['values'] ?? [$where['value']])
            ->map(fn ($handle) => Collection::find($handle));
    }
}
