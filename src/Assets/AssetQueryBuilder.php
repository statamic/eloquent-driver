<?php

namespace Statamic\Eloquent\Assets;

use Illuminate\Support\Collection;
use Statamic\Assets\AssetCollection;
use Statamic\Contracts\Assets\AssetContainer;
use Statamic\Contracts\Assets\QueryBuilder;
use Statamic\Eloquent\QueriesJsonColumns;
use Statamic\Facades;
use Statamic\Fields\Field;
use Statamic\Query\EloquentQueryBuilder;

class AssetQueryBuilder extends EloquentQueryBuilder implements QueryBuilder
{
    use QueriesJsonColumns;

    const COLUMNS = [
        'id', 'container', 'folder', 'basename', 'filename', 'extension', 'path', 'size', 'width', 'height', 'duration', 'mime_type', 'last_modified', 'created_at', 'updated_at',
    ];

    protected function column($column)
    {
        if (! in_array($column, self::COLUMNS)) {
            $column = 'meta->'.$column;
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

    protected function getJsonCasts(): Collection
    {
        $wheres = collect($this->builder->getQuery()->wheres);
        $containerWhere = $wheres->firstWhere('column', 'container');

        if (! $containerWhere || ! isset($containerWhere['value'])) {
            return [
                'size' => 'float',
                'width' => 'float',
                'height' => 'float',
                'duration' => 'float',
            ];
        }

        $container = $containerWhere['value'] instanceof AssetContainer
            ? $containerWhere['value']
            : Facades\AssetContainer::find($containerWhere['value']);

        return $container->blueprint()->fields()->all()
            ->filter(fn (Field $field): bool => in_array($field->type(), ['float', 'integer', 'date']))
            ->map(fn (Field $field): string => $this->toCast($field))
            ->filter()
            ->merge([
                'size' => 'float',
                'width' => 'float',
                'height' => 'float',
                'duration' => 'float',
            ]);
    }
}
