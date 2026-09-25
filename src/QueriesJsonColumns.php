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

    public function whereDate($column, $operator, $value = null, $boolean = 'and')
    {
        if (func_num_args() === 2) {
            [$value, $operator] = [$operator, '='];
        }

        $actualColumn = $this->column($column);

        if ($this->isJsonColumnOnPostgres($actualColumn)) {
            $grammar = $this->builder->getConnection()->getQueryGrammar();
            $wrappedColumn = $grammar->wrap($actualColumn);

            $this->builder->whereRaw(
                "({$wrappedColumn})::timestamp::date {$operator} ?",
                [$value],
                $boolean
            );

            return $this;
        }

        return parent::whereDate($column, $operator, $value, $boolean);
    }

    public function whereMonth($column, $operator, $value = null, $boolean = 'and')
    {
        if (func_num_args() === 2) {
            [$value, $operator] = [$operator, '='];
        }

        $actualColumn = $this->column($column);

        if ($this->isJsonColumnOnPostgres($actualColumn)) {
            return $this->dateBasedWhereJsonPostgres('month', $actualColumn, $operator, $value, $boolean);
        }

        return parent::whereMonth($column, $operator, $value, $boolean);
    }

    public function whereDay($column, $operator, $value = null, $boolean = 'and')
    {
        if (func_num_args() === 2) {
            [$value, $operator] = [$operator, '='];
        }

        $actualColumn = $this->column($column);

        if ($this->isJsonColumnOnPostgres($actualColumn)) {
            return $this->dateBasedWhereJsonPostgres('day', $actualColumn, $operator, $value, $boolean);
        }

        return parent::whereDay($column, $operator, $value, $boolean);
    }

    public function whereYear($column, $operator, $value = null, $boolean = 'and')
    {
        if (func_num_args() === 2) {
            [$value, $operator] = [$operator, '='];
        }

        $actualColumn = $this->column($column);

        if ($this->isJsonColumnOnPostgres($actualColumn)) {
            return $this->dateBasedWhereJsonPostgres('year', $actualColumn, $operator, $value, $boolean);
        }

        return parent::whereYear($column, $operator, $value, $boolean);
    }

    private function isJsonColumnOnPostgres(string $column): bool
    {
        return Str::contains($column, ['data->', 'meta->'])
            && str_contains(get_class($this->builder->getConnection()->getQueryGrammar()), 'PostgresGrammar');
    }

    private function dateBasedWhereJsonPostgres(string $type, string $column, string $operator, $value, string $boolean): static
    {
        $grammar = $this->builder->getConnection()->getQueryGrammar();
        $wrappedColumn = $grammar->wrap($column);

        $this->builder->whereRaw(
            "extract({$type} from ({$wrappedColumn})::timestamp) {$operator} ?",
            [$value],
            $boolean
        );

        return $this;
    }

    abstract protected function getJsonCasts(): Collection;

    protected function toCast(Field $field): string
    {
        $cast = match (true) {
            $field->type() === 'float' => 'float',
            $field->type() === 'integer' => 'float', // A bit sneaky, but MySQL doesn't support casting as integer, it wants unsigned.
            $field->type() === 'date' => $field->get('time_enabled') ? 'datetime' : 'date',
            default => null,
        };

        if ($cast === 'datetime' && str_contains(get_class($this->builder->getConnection()->getQueryGrammar()), 'PostgresGrammar')) {
            $cast = 'timestamp';
        }

        // Date Ranges are dealt with a little bit differently.
        if ($field->type() === 'date' && $field->get('mode') === 'range') {
            $cast = "range_{$cast}";
        }

        return $cast;
    }
}
