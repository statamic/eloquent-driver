<?php

namespace Statamic\Eloquent\Taxonomies;

use Illuminate\Support\Collection;
use Statamic\Contracts\Taxonomies\Taxonomy as TaxonomyContract;
use Statamic\Facades;
use Statamic\Facades\Blink;
use Statamic\Stache\Repositories\TaxonomyRepository as StacheRepository;
use Statamic\Support\Str;

class TaxonomyRepository extends StacheRepository
{
    protected function transform($items, $columns = [])
    {
        return Collection::make($items)->map(function ($model) {
            return app(TaxonomyContract::class)::fromModel($model);
        });
    }

    public static function bindings(): array
    {
        return [
            TaxonomyContract::class => Taxonomy::class,
        ];
    }

    public function all(): Collection
    {
        $models = Blink::once('eloquent-taxonomies', function () {
            return app('statamic.eloquent.taxonomies.model')::all();
        })
            ->each(function ($model) {
                Blink::put("eloquent-taxonomies-{$model->handle}", $model);
            });

        return $this->transform($models);
    }

    public function findByHandle($handle): ?TaxonomyContract
    {
        $taxonomyModel = Blink::once("eloquent-taxonomies-{$handle}", function () use ($handle) {
            return app('statamic.eloquent.taxonomies.model')::whereHandle($handle)->first();
        });

        return $taxonomyModel ? app(TaxonomyContract::class)->fromModel($taxonomyModel) : null;
    }

    public function findByUri(string $uri, string $site = null): ?Taxonomy
    {
        $collection = Facades\Collection::all()
            ->first(function ($collection) use ($uri, $site) {
                if (Str::startsWith($uri, $collection->uri($site))) {
                    return true;
                }

                return Str::startsWith($uri, '/'.$collection->handle());
            });

        if ($collection) {
            $uri = Str::after($uri, $collection->uri($site) ?? $collection->handle());
        }

        // If the collection is mounted to the home page, the uri would have
        // the slash trimmed off at this point. We'll make sure it's there,
        // then look for whats after it to get our handle.
        $uri = Str::after(Str::ensureLeft($uri, '/'), '/');

        return ($taxonomy = $this->findByHandle($uri)) ? $taxonomy->collection($collection) : null;
    }

    public function handles(): Collection
    {
        return $this->all()->map->handle();
    }

    public function save($entry)
    {
        $model = $entry->toModel();
        $model->save();

        $fresh = $model->fresh();

        $entry->model($fresh);

        Blink::put("eloquent-taxonomies-{$fresh->handle}", $fresh);
    }

    public function delete($entry)
    {
        $model = $entry->model();
        $model->delete();

        Blink::forget("eloquent-taxonomies-{$model->handle}");
        Blink::forget('eloquent-taxonomies');
    }
}
