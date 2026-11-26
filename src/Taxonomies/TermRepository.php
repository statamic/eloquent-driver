<?php

namespace Statamic\Eloquent\Taxonomies;

use Statamic\Contracts\Taxonomies\Term as TermContract;
use Statamic\Facades\Blink;
use Statamic\Facades\Entry;
use Statamic\Stache\Repositories\TermRepository as StacheRepository;
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

    public function updateOrders($taxonomy, $ids = null)
    {
        // The order (and, on nestable taxonomies, the URI) of a term is derived
        // from the tree. We only persist copies of them so the database can sort
        // and look terms up by them, so write just those columns without a full
        // save, which would fire events and touch every localization.
        $this->termsToUpdate($taxonomy, $ids)->each(function ($term) {
            $term = $term->term();

            if (! $model = $term->model()) {
                return;
            }

            $model->forceFill([
                'order' => $term->inDefaultLocale()->order(),
                'uri' => $term->uri(),
            ]);

            if (! $model->isDirty()) {
                return;
            }

            $model::withoutTimestamps(fn () => $model->saveQuietly());

            Blink::forget("eloquent-term-{$term->id()}");
        });
    }

    public function updateParents($taxonomy, $ids = null)
    {
        // A term's parent is resolved from the taxonomy tree, so there's nothing
        // to store. A move also changes the URIs of the moved term and its
        // descendants, but those are handled by updateOrders(), which is called
        // with them.
    }

    private function termsToUpdate($taxonomy, $ids)
    {
        if (collect($ids)->isEmpty()) {
            return $taxonomy->queryTerms()->get();
        }

        return collect($ids)
            ->map(fn ($id) => $this->find($id))
            ->filter()
            ->values();
    }
}
