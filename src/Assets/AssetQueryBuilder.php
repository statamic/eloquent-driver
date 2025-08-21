<?php

namespace Statamic\Eloquent\Assets;

use Illuminate\Support\Collection as IlluminateCollection;
use Illuminate\Support\Str;
use Statamic\Assets\AssetCollection;
use Statamic\Contracts\Assets\QueryBuilder;
use Statamic\Fields\Field;
use Statamic\Query\EloquentQueryBuilder;

class AssetQueryBuilder extends EloquentQueryBuilder implements QueryBuilder
{
    const COLUMNS = [
        'id', 'container', 'folder', 'basename', 'filename', 'extension', 'path', 'created_at', 'updated_at',
    ];

    const META_COLUMNS = [
        'size', 'width', 'height', 'duration', 'mime_type', 'last_modified',
    ];

    public function orderBy($column, $direction = 'asc')
    {
        $actualColumn = $this->column($column);

        if (
            Str::contains($actualColumn, 'meta->')
            && $metaColumnCast = $this->getMetaColumnCasts()->get($column)
        ) {
            $grammar = $this->builder->getConnection()->getQueryGrammar();
            $actualColumn = $grammar->wrap($actualColumn);

            // SQLite casts dates to year, which is pretty unhelpful.
            if (
                in_array($metaColumnCast['cast'], ['date', 'datetime'])
                && Str::contains(get_class($grammar), 'SQLiteGrammar')
            ) {
                $this->builder->orderByRaw("datetime({$actualColumn}) {$direction}");

                return $this;
            }

            $this->builder->orderByRaw("cast({$actualColumn} as {$metaColumnCast['cast']}) {$direction}");

            return $this;
        }

        parent::orderBy($column, $direction);

        return $this;
    }

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

    private function getMetaColumnCasts(): IlluminateCollection
    {
        $grammar = $this->builder->getConnection()->getQueryGrammar();

        $wheres = collect($this->builder->getQuery()->wheres);
        $containerWhere = $wheres->firstWhere('column', 'container');

        if (! $containerWhere || ! isset($containerWhere['value'])) {
            return [];
        }

        $container = $containerWhere['value'];

        return $container->blueprint()->fields()->all()
            ->filter(fn (Field $field) => in_array($field->type(), ['float', 'integer', 'date']))
            ->filter()
            ->map(function (Field $field) use ($grammar) {
                $cast = null;

                if ($field->type() === 'float') {
                    $cast = 'float';
                }

                if ($field->type() === 'integer') {
                    $cast = 'float'; // bit sneaky but mysql doesnt support casting as integer, it wants unsigned
                }

                if ($field->type() === 'date') {
                    $cast = $field->get('time_enabled') ? 'datetime' : 'date';

                    if ($field->get('mode') === 'range') {
                        $columnWithoutTheJsonBit = Str::after($field->handle(), '->');

                        $actualColumnStartDate = $grammar->wrap($this->column($columnWithoutTheJsonBit).'->start');
                        $actualColumnEndDate = $grammar->wrap($this->column($columnWithoutTheJsonBit).'->end');

                    }

                    //                    if ($field->get('mode') === 'range') {
                    //                        if (str_contains(get_class($grammar), 'SQLiteGrammar')) {
                    //                            $this->builder
                    //                                ->orderByRaw("datetime({$actualColumnStartDate}) {$direction}")
                    //                                ->orderByRaw("datetime({$actualColumnEndDate}) {$direction}");
                    //                        } else {
                    //                            $this->builder
                    //                                ->orderByRaw("cast({$actualColumnStartDate} as {$castType}) {$direction}")
                    //                                ->orderByRaw("cast({$actualColumnEndDate} as {$castType}) {$direction}");
                    //                        }
                    //
                    //                        return $this;
                    //                    }

                    return [
                        'column' => $field->handle(),
                        'cast' => $cast,
                    ];
                }
            });
    }
}
