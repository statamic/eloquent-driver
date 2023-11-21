<?php

namespace Statamic\Eloquent\Assets;

use Statamic\Assets\AssetCollection;
use Statamic\Contracts\Assets\QueryBuilder;
use Statamic\Query\EloquentQueryBuilder;

class AssetQueryBuilder extends EloquentQueryBuilder implements QueryBuilder
{
    const COLUMNS = [
        'id', 'container', 'folder', 'basename', 'filename', 'extension', 'path', 'created_at', 'updated_at',
    ];

    const META_COLUMNS = [
        'size', 'width', 'height', 'duration', 'mime_type', 'last_modified',
    ];

    protected function column($column)
    {
        if (in_array($column, self::META_COLUMNS)) {
            $column = 'meta->'.$column;
        } elseif (! in_array($column, self::COLUMNS)) {
            $column = 'meta->data->'.$column;
        }

        return $column;
    }

    protected function transform($items, $columns = [])
    {
        return AssetCollection::make($items)->map(function ($model) use ($columns) {
            return app('statamic.eloquent.assets.asset')::fromModel($model)
                ->selectedQueryColumns($this->selectedQueryColumns ?? $columns);
        });
    }

    public function with($relations, $callback = null)
    {
        return $this;
    }
}
