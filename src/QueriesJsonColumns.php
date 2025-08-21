<?php

namespace Statamic\Eloquent;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Statamic\Fields\Field;

trait QueriesJsonColumns
{
    public function orderBy($column, $direction = 'asc')
    {
        $actualColumn = $this->column($column);

        if (
            Str::contains($actualColumn, ['data->', 'meta->'])
            && $jsonCast = $this->getJsonCasts()->get($column)
        ) {
            $grammar = $this->builder->getConnection()->getQueryGrammar();
            $wrappedColumn = $grammar->wrap($actualColumn);

            if (Str::contains($jsonCast, 'range_')) {
                $jsonCast = Str::after($jsonCast, 'range_');

                $wrappedStartDateColumn = $grammar->wrap("{$actualColumn}->start");
                $wrappedEndDateColumn = $grammar->wrap("{$actualColumn}->end");

                if (str_contains(get_class($grammar), 'SQLiteGrammar')) {
                    $this->builder
                        ->orderByRaw("datetime({$wrappedStartDateColumn}) {$direction}")
                        ->orderByRaw("datetime({$wrappedEndDateColumn}) {$direction}");
                } else {
                    $this->builder
                        ->orderByRaw("cast({$wrappedStartDateColumn} as {$jsonCast}) {$direction}")
                        ->orderByRaw("cast({$wrappedEndDateColumn} as {$jsonCast}) {$direction}");
                }

                return $this;
            }

            // SQLite casts dates to year, which is pretty unhelpful.
            if (
                in_array($jsonCast, ['date', 'datetime'])
                && Str::contains(get_class($grammar), 'SQLiteGrammar')
            ) {
                $this->builder->orderByRaw("datetime({$wrappedColumn}) {$direction}");

                return $this;
            }

            $this->builder->orderByRaw("cast({$wrappedColumn} as {$jsonCast}) {$direction}");

            return $this;
        }

        parent::orderBy($column, $direction);

        return $this;
    }

    abstract protected function column($column);

    abstract protected function getJsonCasts(): Collection;

    protected function toCast(Field $field): string
    {
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
    }
}
