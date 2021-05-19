<?php

namespace Statamic\Eloquent\Taxonomies;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Statamic\Contracts\Taxonomies\Taxonomy as TaxonomyContract;
use \Statamic\Stache\Repositories\TaxonomyRepository as StacheRepository;
class TaxonomyRepository extends StacheRepository
{
    protected function transform($items, $columns = [])
    {
        return Collection::make($items)->map(function ($model) {
            return Taxonomy::fromModel($model);
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
        return $this->transform(TaxonomyModel::all());
    }


    public function findByHandle($handle): ?TaxonomyContract
    {
        return app(TaxonomyContract::class)->fromModel(TaxonomyModel::whereHandle($handle)->firstOrFail());
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
}
