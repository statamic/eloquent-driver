<?php

namespace Statamic\Eloquent\Taxonomies;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Statamic\Contracts\Taxonomies\Taxonomy as TaxonomyContract;
use Statamic\Facades\Blink;
use Statamic\Stache\Repositories\TaxonomyRepository as StacheRepository;

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
        $blinkKey = "eloquent-taxonomies-{$handle}";
        $taxonomyModel = Blink::once($blinkKey, function () use ($handle) {
            return app('statamic.eloquent.taxonomies.model')::whereHandle($handle)->first();
        });

        if (! $taxonomyModel instanceof Model) {
            Blink::forget($blinkKey);

            return null;
        }

        return app(TaxonomyContract::class)->fromModel($taxonomyModel);
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
        Blink::forget('eloquent-taxonomies');
    }

    public function delete($entry)
    {
        $model = $entry->model();
        $model->delete();

        Blink::forget("eloquent-taxonomies-{$model->handle}");
        Blink::forget('eloquent-taxonomies');
    }
}
