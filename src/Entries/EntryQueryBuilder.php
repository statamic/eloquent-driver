<?php

namespace Statamic\Eloquent\Entries;

use Illuminate\Support\Str;
use Statamic\Contracts\Entries\QueryBuilder;
use Statamic\Entries\EntryCollection;
use Statamic\Facades\Entry;
use Statamic\Query\EloquentQueryBuilder;
use Statamic\Stache\Query\QueriesTaxonomizedEntries;

class EntryQueryBuilder extends EloquentQueryBuilder implements QueryBuilder
{
    use QueriesTaxonomizedEntries;

    const COLUMNS = [
        'id', 'site', 'origin_id', 'published', 'status', 'slug', 'uri',
        'date', 'collection', 'created_at', 'updated_at',
    ];

    protected $isApplyColumnCheck = true;

    protected function transform($items, $columns = [])
    {
        $items = EntryCollection::make($items)->map(function ($model) {
            return app('statamic.eloquent.entries.entry')::fromModel($model);
        });

        return Entry::applySubstitutions($items);
    }

    protected function column($column)
    {
        if (! is_string($column)) {
            return $column;
        }

        if ($column == 'origin') {
            $column = 'origin_id';
        }

        if ($this->isApplyColumnCheck() && ! in_array($column, self::COLUMNS)) {
            if (! Str::startsWith($column, 'data->')) {
                $column = 'data->'.$column;
            }
        } else {
            $column = parent::column($column);
        }

        return $column;
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

        return parent::paginate($perPage, $columns, $pageName = 'page', $page = null);
    }

    public function count()
    {
        $this->addTaxonomyWheres();

        return parent::count();
    }

    public function setApplyColumnCheck($value = true) {
        $this->isApplyColumnCheck = $value;
        return $this;
    }

    public function isApplyColumnCheck($value = null) {
        return $this->isApplyColumnCheck;
    }
}
