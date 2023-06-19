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

    const COLUMNS = [
        'id', 'site', 'origin_id', 'published', 'status', 'slug', 'uri',
        'date', 'collection', 'created_at', 'updated_at', 'order', 'blueprint',
    ];

    public function orderBy($column, $direction = 'asc')
    {
        $actualColumn = $this->column($column);

        if (Str::contains($actualColumn, 'data->')) {
            $wheres = collect($this->builder->getQuery()->wheres);

            if ($wheres->where('column', 'collection')->count() == 1) {
                if ($collection = Collection::find($wheres->firstWhere('column', 'collection')['value'])) {
                    // could limit by types here (float, integer, date)
                    $blueprintField = $collection->entryBlueprint()->fields()->get($column); // this assumes 1 blue print per collection... dont like it, maybe get all blueprints and merge any fields
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

        parent::orderBy($column, $direction);

        return $this;
    }

    protected function transform($items, $columns = [])
    {
        $items = EntryCollection::make($items)->map(function ($model) use ($columns) {
            return app('statamic.eloquent.entries.entry')::fromModel($model)
                ->selectedQueryColumns($columns);
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
        $this->addTaxonomyWheres();

        return parent::get($columns);
    }

    public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        $this->addTaxonomyWheres();

        return parent::paginate($perPage, $columns, $pageName, $page);
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
}
