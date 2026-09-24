<?php

namespace Statamic\Eloquent\Taxonomies;

use Statamic\Contracts\Taxonomies\Term as TermContract;
use Statamic\Eloquent\Jobs\UpdateTaxonomyTermOrder;
use Statamic\Facades\Blink;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Taxonomy;
use Statamic\Stache\Repositories\TermRepository as StacheRepository;
use Statamic\Support\Str;
use Statamic\Taxonomies\LocalizedTerm;

class TermRepository extends StacheRepository
{
    public function query()
    {
        $this->ensureAssociations();

        return app(TermQueryBuilder::class);
    }

    public function find($id): ?TermContract
    {
        [$handle, $slug] = explode('::', $id);

        $blinkKey = "eloquent-term-{$id}";
        $term = Blink::once($blinkKey, function () use ($handle, $slug) {
            return $this->query()
                ->where('taxonomy', $handle)
                ->where('slug', $slug)
                ->get()
                ->first();
        });

        if (! $term) {
            Blink::forget($blinkKey);

            return null;
        }

        // A term saved earlier in the request is cached here in its raw, locale-less
        // form (see save()). Resolve it the same way the freshly-queried path does,
        // since some Term methods only behave correctly on a LocalizedTerm.
        return $term instanceof LocalizedTerm ? $term : $term->inDefaultLocale();
    }

    public function findByUri(string $uri, ?string $site = null): ?TermContract
    {
        $site = $site ?? $this->stache->sites()->first();

        if ($substitute = $this->substitutionsByUri[$site.'@'.$uri] ?? null) {
            return $substitute;
        }

        $collection = Collection::all()
            ->first(function ($collection) use ($uri, $site) {
                if (Str::startsWith($uri, $collection->uri($site))) {
                    return true;
                }

                return Str::startsWith($uri, '/'.$collection->handle());
            });

        if ($collection) {
            $uri = Str::after($uri, $collection->uri($site) ?? $collection->handle());
        }

        $uri = Str::removeLeft($uri, '/');

        [$taxonomy, $slug] = array_pad(explode('/', $uri), 2, null);

        if (! $slug) {
            return null;
        }

        if (! $taxonomy = $this->findTaxonomyHandleByUri(Str::ensureLeft($taxonomy, '/'))) {
            return null;
        }

        $blinkKey = 'eloquent-term-'.md5(urlencode($uri)).($site ? '-'.$site : '');
        $term = Blink::once($blinkKey, function () use ($slug, $taxonomy) {
            return $this->query()
                ->where('slug', $slug)
                ->where('taxonomy', $taxonomy)
                ->first();
        });

        if (! $term) {
            Blink::forget($blinkKey);

            return null;
        }

        return $term->in($site)?->collection($collection);
    }

    private function findTaxonomyHandleByUri($uri)
    {
        return Taxonomy::all()->first(function ($taxonomy) use ($uri) {
            return $taxonomy->uri() == $uri;
        })?->handle();
    }

    public function save($entry)
    {
        $model = $entry->toModel();
        $model->save();

        $entry->model($model->fresh());

        // Building the model (e.g. resolving a hierarchical term's URI) may have
        // queried and cached the taxonomy's existing term slugs before this term
        // was persisted. Forget it so the next read reflects the saved term.
        if (($taxonomy = $entry->taxonomy()) && $taxonomy->hasStructure()) {
            Blink::forget('taxonomy-structure-term-slugs-'.$taxonomy->handle());
        }

        Blink::put("eloquent-term-{$entry->id()}", $entry);
        Blink::put("eloquent-term-{$entry->uri()}", $entry);
    }

    public function delete($entry)
    {
        $entry->model()->delete();

        Blink::forget("eloquent-term-{$entry->id()}");
        Blink::forget("eloquent-term-{$entry->uri()}");
    }

    public static function bindings(): array
    {
        return [
            TermContract::class => Term::class,
        ];
    }

    protected function ensureAssociations()
    {
        if (config('statamic.eloquent-driver.taxonomies.driver', 'file') === 'eloquent') {
            return;
        }

        parent::ensureAssociations();
    }

    public function entriesCount(TermContract $term, ?string $status = null): int
    {
        if (config('statamic.eloquent-driver.entries.driver', 'file') !== 'eloquent') {
            return parent::entriesCount($term, $status);
        }

        $query = Entry::query()
            ->whereTaxonomy($term->taxonomyHandle().'::'.$term->inDefaultLocale()->slug());

        if ($term instanceof LocalizedTerm) {
            $query->where('site', $term->locale());
        }

        if ($collection = $term->collection()) {
            $query->where('collection', $collection->handle());
        }

        if ($status) {
            $query->whereStatus($status);
        }

        return $query->count();
    }

    public function updateOrders($taxonomy)
    {
        $taxonomy->queryTerms()
            ->get()
            ->each(function ($term) {
                $dispatch = UpdateTaxonomyTermOrder::dispatch($term->id());

                $connection = config('statamic.eloquent-driver.terms.update_term_order_connection', 'default');

                if ($connection != 'default') {
                    $dispatch->onConnection($connection);
                }

                $dispatch->onQueue(config('statamic.eloquent-driver.terms.update_term_order_queue', 'default'));
            });
    }
}
