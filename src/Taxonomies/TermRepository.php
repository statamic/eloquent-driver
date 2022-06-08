<?php

namespace Statamic\Eloquent\Taxonomies;

use Statamic\Contracts\Taxonomies\Term as TermContract;
use Statamic\Stache\Repositories\TermRepository as StacheRepository;
use Statamic\Taxonomies\LocalizedTerm;
use Statamic\Facades\Collection;
use Statamic\Facades\Taxonomy;
use Statamic\Support\Str;

class TermRepository extends StacheRepository
{
    public function query()
    {
        $this->ensureAssociations();

        return app(TermQueryBuilder::class);
    }

    public function find($id): ?LocalizedTerm
    {
        [$handle, $slug] = explode('::', $id);

        $term = $this->query()
            ->where('taxonomy', $handle)
            ->where('slug', $slug)
            ->get();

        return $term ? $term->first() : null;
    }

    public function findByUri(string $uri, string $site = null): ?TermContract
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

        if (! $taxonomy = $this->findTaxonomyHandleByUri($taxonomy)) {
            return null;
        }

        $term = $this->query()
            ->where('slug', $slug)
            ->where('taxonomy', $taxonomy)
            ->first();

        if (! $term) {
            return null;
        }

        return $term->in($site)?->collection($collection);
    }

    private function findTaxonomyHandleByUri($uri)
    {
        return Taxonomy::findByHandle($uri)->handle();
    }

    public function save($entry)
    {
        $model = $entry->toModel();

        $model->save();

        $entry->model($model->fresh());
    }

    public function delete($entry)
    {
        $entry->model()->delete();
    }

    public static function bindings(): array
    {
        return [
            TermContract::class => Term::class,
        ];
    }
}
