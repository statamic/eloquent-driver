<?php


namespace Statamic\Eloquent\Structures;

use Illuminate\Support\Collection;
use Statamic\Contracts\Structures\Nav as NavContract;
use Statamic\Contracts\Taxonomies\Taxonomy as TaxonomyContract;
use Statamic\Eloquent\Taxonomies\Taxonomy;
use Statamic\Eloquent\Taxonomies\TaxonomyModel;
use Statamic\Stache\Repositories\NavigationRepository as StacheRepository;

class NavigationRepository extends StacheRepository
{
    protected function transform($items, $columns = [])
    {
        return Collection::make($items)->map(function ($model) {
            return Nav::fromModel($model);
        });
    }

    public static function bindings(): array
    {
        return [
            NavContract::class => Nav::class,
        ];
    }

    public function all(): Collection
    {
        return $this->transform(NavModel::all());
    }

    public function findByHandle($handle): ?NavContract
    {
        $model = NavModel::whereHandle($handle)->first();
        return $model
            ? app(NavContract::class)->fromModel($model)
            : null;
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
