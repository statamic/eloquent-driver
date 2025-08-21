<?php

namespace Statamic\Eloquent\Assets;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Statamic\Assets\AssetCollection;
use Statamic\Contracts\Assets\AssetContainer;
use Statamic\Contracts\Assets\QueryBuilder;
use Statamic\Facades;
use Statamic\Fields\Field;
use Statamic\Query\EloquentQueryBuilder;

class AssetQueryBuilder extends EloquentQueryBuilder implements QueryBuilder
{
    use QueriesJsonColumns;

    const COLUMNS = [
        'id', 'container', 'folder', 'basename', 'filename', 'extension', 'path', 'created_at', 'updated_at',
    ];

    const META_COLUMNS = [
        'size', 'width', 'height', 'duration', 'mime_type', 'last_modified',
    ];

    protected function column($column): string
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

    protected function getMetaColumnCasts(): Collection
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

        $container = $containerWhere['value'];

        if (! $container instanceof AssetContainer) {
            $container = Facades\AssetContainer::find($container);
        }

        return $container->blueprint()->fields()->all()
            ->filter(fn (Field $field) => in_array($field->type(), ['float', 'integer', 'date']))
            ->filter()
            ->map(function (Field $field): ?string {
                $cast = match (true) {
                    $field->type() === 'float' => 'float',
                    $field->type() === 'integer' => 'float', // A bit sneaky, but MySQL doesn't support casting as integer, it wants unsigned.
                    $field->type() === 'date' => $field->get('time_enabled') ? 'datetime' : 'date',
                    default => null,
                };

                // Date Ranges are dealt with a little bit differently.
                if ($field->type() === 'date' && $field->get('mode') === 'range') {
                    $cast = "range_{$cast}";
                }

                return $cast;
            })
            ->filter()
            ->merge([
                'size' => 'float',
                'width' => 'float',
                'height' => 'float',
                'duration' => 'float',
            ]);
    }
}
