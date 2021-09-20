<?php

namespace Statamic\Eloquent\Collections;

use Illuminate\Support\Collection as IlluminateCollection;
use Statamic\Contracts\Entries\Collection as CollectionContract;
use Statamic\Eloquent\Entries\EntryModel;
use Statamic\Stache\Repositories\CollectionRepository as StacheRepository;

class CollectionRepository extends StacheRepository
{
    public function updateEntryUris($collection, $ids = null)
    {
        $query = $collection->queryEntries();

        if ($ids) {
            $query->whereIn('id', $ids);
        }

        $query->get()->each(function ($entry) {
            EntryModel::where('id', $entry->id())->update(['uri' => $entry->uri()]);
        });
    }

    public function all(): IlluminateCollection
    {
        return $this->transform(CollectionModel::all());
    }

    public function find($handle): ?CollectionContract
    {
        $model = CollectionModel::whereHandle($handle)->first();

        return $model
            ? app(CollectionContract::class)->fromModel($model)
            : null;
    }

    public function findByHandle($handle): ?CollectionContract
    {
        $model = CollectionModel::whereHandle($handle)->first();

        return $model
            ? app(CollectionContract::class)->fromModel($model)
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

    protected function transform($items, $columns = [])
    {
        return IlluminateCollection::make($items)->map(function ($model) {
            return Collection::fromModel($model);
        });
    }

    public static function bindings(): array
    {
        return [
            CollectionContract::class => app('statamic.eloquent.collections.entry'),
        ];
    }
}
