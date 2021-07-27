<?php

namespace Statamic\Eloquent\Taxonomies;

use Statamic\Query\EloquentQueryBuilder;
use Statamic\Taxonomies\TermCollection;

class TermQueryBuilder extends EloquentQueryBuilder
{
    protected $site = null;

    protected $columns = [
        'id', 'site', 'slug', 'uri', 'taxonomy', 'created_at', 'updated_at',
    ];

    protected function transform($items, $columns = [])
    {
        return TermCollection::make($items)->map(function ($model) {
            return Term::fromModel($model)->in($this->site);
        });
    }

    protected function column($column)
    {
        if (! in_array($column, $this->columns)) {
            $column = 'data->'.$column;
        }

        return $column;
    }

    public function where($column, $operator = null, $value = null)
    {
        if ($column === 'site') {
            $this->site = $operator;

            return $this;
        }

        return parent::where($column, $operator, $value);
    }
}
