<?php

namespace Statamic\Eloquent\Revisions;

use Illuminate\Support\Collection as IlluminateCollection;
use Illuminate\Support\Str;
use Statamic\Contracts\Revisions\Revision as RevisionContract;
use Statamic\Contracts\Revisions\RevisionQueryBuilder as QueryBuilderContract;
use Statamic\Query\EloquentQueryBuilder;

class RevisionQueryBuilder extends EloquentQueryBuilder implements QueryBuilderContract
{
    private $selectedQueryColumns;

    const COLUMNS = [
        'id', 'key', 'action', 'user', 'message', 'attributes',
    ];

    protected function transform($items, $columns = [])
    {
        return IlluminateCollection::make($items)->map(function ($model) use ($columns) {
            return app(RevisionContract::class)::fromModel($model)
                ->selectedQueryColumns($this->selectedQueryColumns ?? $columns);
        });
    }

    protected function column($column)
    {
        if (! is_string($column)) {
            return $column;
        }

        if (! in_array($column, self::COLUMNS)) {
            if (! Str::startsWith($column, 'attributes->')) {
                $column = 'attributes->'.$column;
            }
        }

        return $column;
    }

    public function with($relations, $callback = null)
    {
        return $this;
    }
}
