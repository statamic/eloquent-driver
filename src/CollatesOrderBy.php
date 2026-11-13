<?php

namespace Statamic\Eloquent;

use Illuminate\Database\Connection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Statamic\Eloquent\Database\SqliteCollation;

trait CollatesOrderBy
{
    public function orderBy($column, $direction = 'asc')
    {
        $direction = strtolower($direction);

        if (! in_array($direction, ['asc', 'desc'], true)) {
            throw new InvalidArgumentException('Order direction must be "asc" or "desc".');
        }

        $this->orderByColumn($column, $direction);

        return $this;
    }

    public function orderByDesc($column)
    {
        return $this->orderBy($column, 'desc');
    }

    public function reorder($column = null, $direction = 'asc')
    {
        $this->builder->reorder();

        return $column ? $this->orderBy($column, $direction) : $this;
    }

    protected function orderByColumn($column, string $direction): void
    {
        $actualColumn = $this->column($column);

        if (! $collation = $this->collationFor($actualColumn)) {
            $this->builder->orderBy($actualColumn, $direction);

            return;
        }

        $wrapped = $this->builder->getConnection()->getQueryGrammar()->wrap($actualColumn);

        $this->builder->orderByRaw("{$wrapped} collate {$collation} {$direction}");
    }

    protected function collationFor($column): ?string
    {
        if (! is_string($column) || ! config('statamic.eloquent-driver.alphabetical_sorting', false)) {
            return null;
        }

        $connection = $this->builder->getConnection();

        return match ($connection->getDriverName()) {
            'sqlite' => $this->shouldCollate($column) ? SqliteCollation::for($connection) : null,
            // Columns already use the table collation, but JSON values are always utf8mb4_bin.
            'mysql', 'mariadb' => Str::contains($column, '->') ? $this->mysqlCollation($connection) : null,
            default => null,
        };
    }

    protected function shouldCollate(string $column): bool
    {
        // Collating an indexed column stops SQLite from using the index to sort.
        return ! in_array(Str::afterLast($column, '.'), $this->uncollatedColumns(), true);
    }

    private function mysqlCollation(Connection $connection): string
    {
        $collation = (string) $connection->getConfig('collation');

        return preg_match('/^utf8mb4_\w+$/', $collation) ? $collation : 'utf8mb4_unicode_ci';
    }

    protected function uncollatedColumns(): array
    {
        return $this->modelUncollatedColumns();
    }

    protected function modelUncollatedColumns(): array
    {
        $model = $this->builder->getModel();

        return [
            $model->getKeyName(),
            $model->getCreatedAtColumn(),
            $model->getUpdatedAtColumn(),
            ...array_keys($model->getCasts()),
        ];
    }
}
