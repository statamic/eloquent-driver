<?php

namespace Statamic\Eloquent\Taxonomies;

use Illuminate\Support\Collection;
use Statamic\Contracts\Taxonomies\Taxonomy as TaxonomyContract;
use Statamic\Facades\Blink;
use Statamic\Stache\Repositories\TaxonomyRepository as StacheRepository;

class TaxonomyRepository extends StacheRepository
{
    protected function transform($items, $columns = [])
    {
        return Collection::make($items)->map(function ($model) {
            return Blink::once("eloquent-taxonomies-{$model->handle}", function() use ($model) {
                return app(TaxonomyContract::class)::fromModel($model);
            });
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
        return Blink::once("eloquent-taxonomies-all", function() {
            return $this->transform(app('statamic.eloquent.taxonomies.model')::all());
        });
    }

    public function findByHandle($handle): ?TaxonomyContract
    {
        return Blink::once("eloquent-taxonomies-{$handle}", function() use ($handle) {
            $taxonomyModel = app('statamic.eloquent.taxonomies.model')::whereHandle($handle)->first();
    
            return $taxonomyModel
                ? app(TaxonomyContract::class)->fromModel($taxonomyModel)
                : null;
        });
    }

    public function save($entry)
    {
        $model = $entry->toModel();
        $model->save();

        $entry->model($model->fresh());
        
        Blink::forget("eloquent-taxonomies-{$model->handle}");
    }

    public function delete($entry)
    {
        $model = $entry->model();
        $model->delete();
        
        Blink::forget("eloquent-taxonomies-{$model->handle}");
        Blink::forget("eloquent-taxonomies-all");
    }
}
