<?php


namespace Statamic\Eloquent\Taxonomies;

use Statamic\Eloquent\Entries\Entry;
use Statamic\Entries\EntryCollection;
use Statamic\Query\EloquentQueryBuilder;
use Statamic\Taxonomies\TermCollection;


class TermQueryBuilder extends EloquentQueryBuilder
{
    protected $columns = [
        'id', 'site', 'slug', 'uri', 'taxonomy', 'created_at', 'updated_at',
    ];

    protected function transform($items, $columns = [])
    {
        return TermCollection::make($items)->map(function ($model) {
            return Term::fromModel($model);
        });
    }

    protected function column($column)
    {
        if ($column == 'origin') {
            $column = 'origin_id';
        }

        if (! in_array($column, $this->columns)) {
            $column = 'data->'.$column;
        }

        return $column;
    }
}
