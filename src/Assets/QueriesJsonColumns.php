<?php

namespace Statamic\Eloquent\Assets;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

trait QueriesJsonColumns
{
    public function orderBy($column, $direction = 'asc')
    {
        $actualColumn = $this->column($column);

        if (
            Str::contains($actualColumn, 'meta->')
            && $metaColumnCast = $this->getMetaColumnCasts()->get($column)
        ) {
            $grammar = $this->builder->getConnection()->getQueryGrammar();
            $wrappedColumn = $grammar->wrap($actualColumn);

            if (Str::contains($metaColumnCast, 'range_')) {
                $metaColumnCast = Str::after($metaColumnCast, 'range_');

                $wrappedStartDateColumn = $grammar->wrap("{$actualColumn}->start");
                $wrappedEndDateColumn = $grammar->wrap("{$actualColumn}->end");

                if (str_contains(get_class($grammar), 'SQLiteGrammar')) {
                    $this->builder
                        ->orderByRaw("datetime({$wrappedStartDateColumn}) {$direction}")
                        ->orderByRaw("datetime({$wrappedEndDateColumn}) {$direction}");
                } else {
                    $this->builder
                        ->orderByRaw("cast({$wrappedStartDateColumn} as {$metaColumnCast}) {$direction}")
                        ->orderByRaw("cast({$wrappedEndDateColumn} as {$metaColumnCast}) {$direction}");
                }

                return $this;
            }

            // SQLite casts dates to year, which is pretty unhelpful.
            if (
                in_array($metaColumnCast, ['date', 'datetime'])
                && Str::contains(get_class($grammar), 'SQLiteGrammar')
            ) {
                $this->builder->orderByRaw("datetime({$wrappedColumn}) {$direction}");

                return $this;
            }

            $this->builder->orderByRaw("cast({$wrappedColumn} as {$metaColumnCast}) {$direction}");

            return $this;
        }

        parent::orderBy($column, $direction);

        return $this;
    }

    abstract protected function column($column): string;

    abstract protected function getMetaColumnCasts(): Collection;
}