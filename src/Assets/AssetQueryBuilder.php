<?php

namespace Statamic\Eloquent\Assets;

use Illuminate\Support\Str;
use Statamic\Assets\AssetCollection;
use Statamic\Contracts\Assets\QueryBuilder;
use Statamic\Facades\Collection;
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

        if (Str::contains($actualColumn, 'meta->')) {
            $wheres = collect($this->builder->getQuery()->wheres);

            if ($wheres->where('column', 'container')->count() == 1) {
                $containerWhere = $wheres->firstWhere('column', 'container');
                if (isset($containerWhere['values']) && count($containerWhere['values']) == 1) {
                    $containerWhere['value'] = $containerWhere['values'][0];
                }

                if (isset($containerWhere['value'])) {
                    // todo: the entryquerybuilder expects value to be a string, but here it seems to be an AssetContainer instance
                    if ($container = $containerWhere['value']) {
                        $blueprintField = $container->blueprint()->fields()->all()
                            ->filter(fn ($field) => in_array($field->type(), ['float', 'integer', 'date']))
                            ->filter()
                            ->merge(['size' => new Field('size', ['type' => 'integer'])])
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
                                // Take time into account when enabled
                                if ($blueprintField->get('time_enabled')) {
                                    $castType = 'datetime';
                                } else {
                                    $castType = 'date';
                                }

                                // take range into account
                                if ($blueprintField->get('mode') == 'range') {
                                    $actualColumnStartDate = $grammar->wrap($this->column($column).'->start');
                                    $actualColumnEndDate = $grammar->wrap($this->column($column).'->end');
                                    if (str_contains(get_class($grammar), 'SQLiteGrammar')) {
                                        $this->builder
                                            ->orderByRaw("datetime({$actualColumnStartDate}) {$direction}")
                                            ->orderByRaw("datetime({$actualColumnEndDate}) {$direction}");
                                    } else {
                                        $this->builder
                                            ->orderByRaw("cast({$actualColumnStartDate} as {$castType}) {$direction}")
                                            ->orderByRaw("cast({$actualColumnEndDate} as {$castType}) {$direction}");
                                    }

                                    return $this;
                                }

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
