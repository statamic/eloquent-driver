<?php

namespace Statamic\Eloquent\Entries;

use Illuminate\Support\Collection as IlluminateCollection;
use Illuminate\Support\Str;
use Statamic\Contracts\Entries\QueryBuilder;
use Statamic\Eloquent\Entries\Entry as EloquentEntry;
use Statamic\Eloquent\QueriesJsonColumns;
use Statamic\Entries\EntryCollection;
use Statamic\Facades\Blink;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Taxonomy;
use Statamic\Fields\Field;
use Statamic\Query\EloquentQueryBuilder;
use Statamic\Stache\Query\QueriesEntryStatus;
use Statamic\Stache\Query\QueriesTaxonomizedEntries;

class EntryQueryBuilder extends EloquentQueryBuilder implements QueryBuilder
{
    use QueriesEntryStatus,
        QueriesJsonColumns,
        QueriesTaxonomizedEntries;

    private $selectedQueryColumns;

    private const STATUSES = ['published', 'draft', 'scheduled', 'expired'];

    const COLUMNS = [
        'id', 'site', 'origin_id', 'published', 'slug', 'uri', 'data',
        'date', 'collection', 'created_at', 'updated_at', 'order', 'blueprint',
    ];

    protected function transform($items, $columns = [])
    {
        $items = EntryCollection::make($items)->map(function ($model) use ($columns) {
            return app('statamic.eloquent.entries.entry')::fromModel($model)
                ->selectedQueryColumns($this->selectedQueryColumns ?? $columns);
        });

        return Entry::applySubstitutions($items);
    }

    protected function column($column)
    {
        if (! is_string($column)) {
            return $column;
        }

        $table = Str::contains($column, '.') ? Str::before($column, '.') : '';
        $column = Str::after($column, '.');

        if ($column == 'origin') {
            $column = 'origin_id';
        }

        $columns = Blink::once('eloquent-entry-data-column-mappings', fn () => array_merge(self::COLUMNS, (new EloquentEntry)->getDataColumnMappings($this->builder->getModel())));

        if (! in_array($column, $columns)) {
            if (! Str::startsWith($column, 'data->')) {
                $column = 'data->'.$column;
            }
        }

        return ($table ? $table.'.' : '').$column;
    }

    public function find($id, $columns = ['*'])
    {
        $model = parent::find($id, $columns);

        if ($model) {
            return app('statamic.eloquent.entries.entry')::fromModel($model)
                ->selectedQueryColumns($columns);
        }
    }

    public function get($columns = ['*'])
    {
        $query = $this->builder->getQuery();
        if ($query->offset && ! $query->limit) {
            $query->limit = PHP_INT_MAX;
        }

        $this->selectedQueryColumns = $columns;

        $this->addTaxonomyWheres();

        return parent::get();
    }

    public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        $this->addTaxonomyWheres();

        $this->selectedQueryColumns = $columns;

        return parent::paginate($perPage, ['*'], $pageName, $page);
    }

    public function count()
    {
        $this->addTaxonomyWheres();

        return parent::count();
    }

    public function with($relations, $callback = null)
    {
        return $this;
    }

    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        if ($column === 'status') {
            trigger_error('Filtering by status is deprecated. Use whereStatus() instead.', E_USER_DEPRECATED);
        }

        return parent::where(...func_get_args());
    }

    public function whereIn($column, $values, $boolean = 'and')
    {
        if ($column === 'status') {
            trigger_error('Filtering by status is deprecated. Use whereStatus() instead.', E_USER_DEPRECATED);
        }

        return parent::whereIn(...func_get_args());
    }

    private function ensureCollectionsAreQueriedForStatusQuery(): void
    {
        $wheres = collect($this->builder->getQuery()->wheres);

        // If there are where clauses on the collection column, it means the user has explicitly
        // queried for them. In that case, we'll use them and skip the auto-detection.
        if ($wheres->where('column', 'collection')->isNotEmpty()) {
            return;
        }

        // Otherwise, we'll detect them by looking at where clauses targeting the "id" column.
        $ids = $wheres->where('column', 'id')->flatMap(fn ($where) => $where['values'] ?? [$where['value']]);

        // If no IDs were queried, fall back to all collections.
        $ids->isEmpty()
            ? $this->whereIn('collection', Collection::handles())
            : $this->whereIn('collection', app(static::class)->whereIn('id', $ids)->pluck('collection')->map(fn ($collection) => $collection?->handle)->filter()->unique()->values());
    }

    private function getCollectionsForStatusQuery(): \Illuminate\Support\Collection
    {
        // Since we have to add nested queries for each collection, we only want to add clauses for the
        // applicable collections. By this point, there should be where clauses on the collection column.

        return collect($this->builder->getQuery()->wheres)
            ->where('column', 'collection')
            ->flatMap(fn ($where) => $where['values'] ?? [$where['value']])
            ->map(fn ($handle) => Collection::find($handle));
    }

    private function getKeysForTaxonomyWhereBasic($where)
    {
        $term = $where['value'];

        [$taxonomy, $slug] = explode('::', $term);

        if (! $taxonomy = Taxonomy::find($taxonomy)) {
            return collect();
        }

        return app('statamic.eloquent.entries.model')::query()
            ->select(['id'])
            ->whereIn('collection', $taxonomy->collections()->map->handle()->all())
            ->whereJsonContains($this->column($taxonomy->handle()), $slug)
            ->get()
            ->pluck('id');
    }

    private function getKeysForTaxonomyWhereIn($where)
    {
        // Get the terms grouped by taxonomy.
        // [tags::foo, categories::baz, tags::bar]
        // becomes [tags => [foo, bar], categories => [baz]]
        $taxonomies = collect($where['values'])
            ->map(function ($value) {
                [$taxonomy, $term] = explode('::', $value);

                return compact('taxonomy', 'term');
            })
            ->groupBy->taxonomy
            ->map(function ($group) {
                return collect($group)->map->term;
            });

        return $taxonomies->flatMap(function ($terms, $taxonomy) {
            if (! $taxonomy = Taxonomy::find($taxonomy)) {
                return collect();
            }

            return app('statamic.eloquent.entries.model')::query()
                ->select(['id'])
                ->whereIn('collection', $taxonomy->collections()->map->handle()->all())
                ->where(function ($query) use ($taxonomy, $terms) {
                    foreach ($terms as $term) {
                        $query->orWhereJsonContains($this->column($taxonomy->handle()), $term);
                    }
                })
                ->get()
                ->pluck('id');
        });
    }

    protected function getJsonCasts(): IlluminateCollection
    {
        $wheres = collect($this->builder->getQuery()->wheres);
        $collectionWhere = $wheres->firstWhere('column', 'collection');

        if (
            ! $collectionWhere
            || ! isset($collectionWhere['value'])
            || ! ($collection = Collection::find($collectionWhere['value']))
        ) {
            return collect([]);
        }

        return $collection->entryBlueprints()
            ->flatMap(function ($blueprint) {
                return $blueprint->fields()
                    ->all()
                    ->filter(fn ($field) => in_array($field->type(), ['float', 'integer', 'date']))
                    ->map(fn (Field $field) => $this->toCast($field));
            })
            ->filter();
    }
}
