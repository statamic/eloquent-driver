<?php

namespace Statamic\Eloquent\Taxonomies;

use DateTimeInterface;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Statamic\Contracts\Taxonomies\Term as TermContract;
use Statamic\Eloquent\CollatesOrderBy;
use Statamic\Facades\Blink;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Facades\Taxonomy;
use Statamic\Facades\Term;
use Statamic\Query\EloquentQueryBuilder;
use Statamic\Taxonomies\TermCollection;

class TermQueryBuilder extends EloquentQueryBuilder
{
    use CollatesOrderBy;

    protected $collections = [];

    protected $collectionContext = null;

    protected $taxonomies = [];

    protected $columns = [
        'id', 'data', 'site', 'slug', 'uri', 'taxonomy', 'created_at', 'updated_at',
    ];

    protected function transform($items, $columns = [])
    {
        return TermCollection::make($items)->map(function ($model) {
            return app(TermContract::class)::fromModel($model)->in($model->site ?? Site::default()->handle());
        });
    }

    protected function column($column)
    {
        if (! is_string($column)) {
            return $column;
        }

        if (! in_array($column, $this->columns)) {
            if (! Str::startsWith($column, 'data->')) {
                $column = 'data->'.$column;
            }
        }

        return $column;
    }

    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        if (func_num_args() === 2) {
            [$value, $operator] = [$operator, '='];
        }

        if (in_array($column, ['taxonomy', 'taxonomies'])) {
            if (! $value) {
                return $this;
            }

            if (! is_array($value)) {
                $value = [$value];
            }

            $this->taxonomies = array_merge($this->taxonomies, $value);

            return $this;
        }

        if (in_array($column, ['collection', 'collections'])) {
            if (! $value) {
                return $this;
            }

            if (! is_array($value)) {
                $value = [$value];
            }

            $this->collections = array_merge($this->collections, $value);

            return $this;
        }

        if (in_array($column, ['id', 'slug'])) {
            $column = 'slug';

            if (str_contains($value, '::')) {
                $taxonomy = Str::before($value.'', '::');

                if ($taxonomy) {
                    $this->taxonomies[] = $taxonomy;
                }

                $value = Str::after($value, '::');
            }
        }

        parent::where($column, $operator, $value, $boolean);

        return $this;
    }

    public function whereIn($column, $values, $boolean = 'and')
    {
        if (in_array($column, ['taxonomy', 'taxonomies'])) {
            if (! $values) {
                return $this;
            }

            $this->taxonomies = array_merge($this->taxonomies, collect($values)->all());

            return $this;
        }

        if (in_array($column, ['collection', 'collections'])) {
            if (! $values) {
                return $this;
            }

            $this->collections = array_merge($this->collections, collect($values)->all());

            return $this;
        }

        if (in_array($column, ['id', 'slug'])) {
            $column = 'slug';
            $values = collect($values)
                ->map(function ($value) {
                    if (! str_contains($value, '::')) {
                        return $value;
                    }

                    if ($taxonomy = Str::before($value, '::')) {
                        $this->taxonomies[] = $taxonomy;
                    }

                    return Str::after($value, '::');
                })
                ->all();
        }

        parent::whereIn($column, $values, $boolean);

        return $this;
    }

    /**
     * Supply a collection to the resulting terms, so things like URLs will be
     * scoped to it, without limiting results to terms used in that collection
     * the way where('collection', ...) does. That usage filter needs a scan of
     * the entries table, which the Terms fieldtype doesn't need.
     */
    public function withCollectionContext($collection)
    {
        $this->collectionContext = $collection;

        return $this;
    }

    public function find($id, $columns = ['*'])
    {
        $model = parent::find($id, $columns);

        if ($model) {
            return app(TermContract::class)::fromModel($model)
                ->in($model->site ?? Site::default()->handle())
                ->selectedQueryColumns($columns);
        }
    }

    public function get($columns = ['*'])
    {
        $this->applyCollectionAndTaxonomyWheres();

        // Ensure 'taxonomy' is always selected as it's required for Term::fromModel().
        if (! empty($columns) && ! in_array('*', $columns) && ! in_array('taxonomy', $columns)) {
            $columns[] = 'taxonomy';
        }

        return $this->prepareTerms(parent::get($columns));
    }

    private function prepareTerms($items)
    {
        // If a single collection has been queried, we'll supply it to the terms so
        // things like URLs will be scoped to the collection. We can't do it when
        // multiple collections are queried because it would be ambiguous.
        $collection = $this->collections && count($this->collections) == 1
            ? $this->collections[0]
            : $this->collectionContext;

        if ($collection) {
            $items->each->collection(Collection::findByHandle($collection));
        }

        return Term::applySubstitutions($items);
    }

    public function pluck($column, $key = null)
    {
        $this->applyCollectionAndTaxonomyWheres();

        return parent::pluck($column, $key);
    }

    public function count()
    {
        $this->applyCollectionAndTaxonomyWheres();

        return parent::count();
    }

    public function paginate($perPage = null, $columns = [], $pageName = 'page', $page = null)
    {
        $this->applyCollectionAndTaxonomyWheres();

        // Ensure 'taxonomy' is always selected as it's required for Term::fromModel().
        if (! empty($columns) && ! in_array('*', $columns) && ! in_array('taxonomy', $columns)) {
            $columns[] = 'taxonomy';
        }

        $paginator = parent::paginate($perPage, $columns, $pageName, $page);

        return $paginator->setCollection($this->prepareTerms($paginator->getCollection()));
    }

    private function applyCollectionAndTaxonomyWheres()
    {
        if (! empty($this->collections) && ! $this->hasEmptyWhereIn()) {
            $taxonomies = collect(empty($this->taxonomies) ? Taxonomy::handles()->all() : $this->taxonomies)
                ->unique()
                ->map(fn ($taxonomy) => Taxonomy::find($taxonomy))
                ->filter();

            if ($taxonomies->isEmpty()) {
                $this->builder->whereRaw('1 = 0');
            } elseif (config('statamic.eloquent-driver.entries.driver', 'file') == 'eloquent') {
                $this->whereUsedInCollections($taxonomies);
            } else {
                $this->whereUsedInFileCollections($taxonomies);
            }
        }

        if (! empty($this->taxonomies)) {
            $queryTaxonomies = collect($this->taxonomies)
                ->filter()
                ->unique();

            if ($queryTaxonomies->count() > 0) {
                $this->builder->whereIn('taxonomy', $queryTaxonomies->all());
            }
        }
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

            if (! ($value instanceof DateTimeInterface)) {
                $value = Carbon::parse($value);
            }

            $this->builder->whereRaw(
                "({$wrappedColumn})::timestamp::date {$operator} ?",
                [$value->format('Y-m-d')],
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
        return Str::contains($column, 'data->')
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

    /**
     * Limit to terms used by entries in the queried collections.
     *
     * When the query targets specific terms (e.g. the handful of ids being augmented
     * for a Terms field) usage is checked with a correlated EXISTS, so the database
     * only checks those rows and can stop at the first matching entry. Otherwise
     * every used slug is gathered in a single pass over the collection's entries,
     * rather than checking each term in the taxonomy against every entry.
     */
    private function whereUsedInCollections($taxonomies)
    {
        $entriesTable = (new (app('statamic.eloquent.entries.model')))->getTable();
        $termsTable = (new (app('statamic.eloquent.terms.model')))->getTable();

        $narrowed = $this->isNarrowedToSpecificTerms();

        $this->builder->where(function ($query) use ($taxonomies, $entriesTable, $termsTable, $narrowed) {
            $taxonomies->each(function ($taxonomy) use ($query, $entriesTable, $termsTable, $narrowed) {
                $query->orWhere(function ($query) use ($taxonomy, $entriesTable, $termsTable, $narrowed) {
                    $query->where("{$termsTable}.taxonomy", $taxonomy->handle());

                    if (! $narrowed && $usedSlugs = $this->usedSlugsQuery($taxonomy, $entriesTable)) {
                        $query->whereIn("{$termsTable}.slug", $usedSlugs);

                        return;
                    }

                    $query->whereExists(function ($query) use ($taxonomy, $entriesTable, $termsTable) {
                        $wrappedColumn = $query->getGrammar()->wrap("{$termsTable}.slug");
                        $value = match ($query->getConnection()->getDriverName()) {
                            'sqlite' => new Expression($wrappedColumn),
                            'pgsql' => new Expression("to_jsonb({$wrappedColumn}::text)"),
                            default => DB::raw("concat('\"', {$wrappedColumn}, '\"')"),
                        };

                        $query->select(DB::raw(1))
                            ->from($entriesTable)
                            ->whereIn("{$entriesTable}.collection", $this->collections)
                            ->whereJsonContains(Entry::query()->column($taxonomy->handle()), $value);
                    });
                });
            });
        });
    }

    /**
     * A subquery selecting every distinct term slug stored in the taxonomy's field on
     * entries in the queried collections, or null when the database can't unnest JSON
     * arrays (MySQL < 8.0.4, MariaDB < 10.6, SQL Server), so EXISTS is used instead.
     * Single values (e.g. from max_items: 1 fields) are treated as one item arrays.
     */
    private function usedSlugsQuery($taxonomy, string $entriesTable)
    {
        $connection = $this->builder->getConnection();

        [$field, $path] = $this->jsonFieldAndPath(Entry::query()->column($taxonomy->handle()));

        $field = $connection->getQueryGrammar()->wrap("{$entriesTable}.{$field}");

        $query = $connection->query()
            ->from($entriesTable)
            ->whereIn("{$entriesTable}.collection", $this->collections)
            ->distinct();

        switch ($connection->getDriverName()) {
            case 'sqlite':
                $query
                    ->crossJoin(new Expression("json_each({$field}, '{$this->jsonPathString($path)}') as used_terms"))
                    ->select('used_terms.value as slug');
                break;

            case 'mysql':
            case 'mariadb':
                $minimum = $connection->isMaria() ? '10.6.0' : '8.0.4';

                if (version_compare($connection->getServerVersion(), $minimum, '<')) {
                    return null;
                }

                // A binary column matches slugs exactly, like JSON_CONTAINS does, and
                // avoids collation mismatches between the connection and the table.
                $value = "json_extract({$field}, '{$this->jsonPathString($path)}')";

                $query
                    ->crossJoin(new Expression("json_table(if(json_type({$value}) = 'ARRAY', {$value}, json_array({$value})), '\$[*]' columns (slug varchar(255) character set binary path '\$')) as used_terms"))
                    ->select('used_terms.slug');
                break;

            case 'pgsql':
                $value = $path
                    ? "({$field})::jsonb #> '{".collect($path)->map(fn ($key) => '"'.str_replace(['\\', '"', "'"], ['\\\\', '\\"', "''"], $key).'"')->join(',')."}'"
                    : "({$field})::jsonb";

                $query->select(new Expression(
                    "jsonb_array_elements_text(case when jsonb_typeof({$value}) = 'array' then {$value} else jsonb_build_array({$value}) end) as slug"
                ));
                break;

            default:
                return null;
        }

        // Selecting from a derived table makes the database work out the used slugs
        // once. Otherwise MySQL can turn the IN into a semi-join that unnests every
        // entry again for each term.
        return $connection->query()->fromSub($query, 'used_slugs')->select('used_slugs.slug');
    }

    /**
     * Split a column like "data->seo->tags" into its field ("data") and path (["seo", "tags"]).
     */
    private function jsonFieldAndPath(string $column): array
    {
        $parts = explode('->', $column);

        return [array_shift($parts), $parts];
    }

    private function jsonPathString(array $path): string
    {
        return collect($path)
            ->map(fn ($key) => '."'.str_replace(['\\', '"', "'"], ['\\\\', '\\"', "''"], $key).'"')
            ->prepend('$')
            ->join('');
    }

    /**
     * Whether the query is limited to specific terms, i.e. every top level where is an
     * "and" and one of them matches slugs (or ids) exactly.
     */
    private function isNarrowedToSpecificTerms(): bool
    {
        $wheres = collect($this->builder->getQuery()->wheres);

        return $wheres->every(fn ($where) => $where['boolean'] === 'and')
            && $wheres->contains(fn ($where) => in_array($where['column'] ?? null, ['slug', 'id'])
                && ($where['type'] === 'In' || ($where['type'] === 'Basic' && $where['operator'] === '=')));
    }

    private function whereUsedInFileCollections($taxonomies)
    {
        $this->builder->where(function ($query) use ($taxonomies) {
            $taxonomies->each(function ($taxonomy) use ($query) {
                $collectionTaxonomyHash = md5(collect($this->collections)->merge([$taxonomy->handle()])->sort()->join('-'));

                $terms = Blink::once("eloquent-taxonomy-hash-{$collectionTaxonomyHash}", function () use ($taxonomy) {
                    return TermModel::where('taxonomy', $taxonomy->handle())
                        ->select('slug')
                        ->get()
                        ->map(function ($term) use ($taxonomy) {
                            return Entry::query()
                                ->whereIn('collection', $this->collections)
                                ->whereJsonContains($taxonomy->handle(), [$term->slug])
                                ->count() > 0 ? $term->slug : null;
                        })
                        ->filter()
                        ->values();
                });

                $query->orWhere(function ($query) use ($terms, $taxonomy) {
                    $query->where('taxonomy', $taxonomy->handle())
                        ->whereIn('slug', $terms->all());
                });
            });
        });
    }

    /**
     * Whether the query can't return anything because every top level where is
     * an "and" and one of them is a whereIn with no values, e.g. augmenting an
     * empty Terms field. There's no point checking usage within collections.
     */
    private function hasEmptyWhereIn(): bool
    {
        $wheres = collect($this->builder->getQuery()->wheres);

        return $wheres->every(fn ($where) => $where['boolean'] === 'and')
            && $wheres->contains(fn ($where) => $where['type'] === 'In' && empty($where['values']));
    }

    public function with($relations, $callback = null)
    {
        return $this;
    }

    protected function getBlueprintsForRelations()
    {
        $taxonomies = empty($this->taxonomies)
            ? Taxonomy::handles()
            : $this->taxonomies;

        return collect($taxonomies)->flatMap(function ($taxonomy) {
            if (is_string($taxonomy)) {
                $taxonomy = Taxonomy::find($taxonomy);
            }

            return $taxonomy ? $taxonomy->termBlueprints() : false;
        })
            ->filter()
            ->unique();
    }
}
