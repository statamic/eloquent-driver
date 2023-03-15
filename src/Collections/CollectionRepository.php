<?php

namespace Statamic\Eloquent\Collections;

use Illuminate\Support\Collection as IlluminateCollection;
use Statamic\Contracts\Entries\Collection as CollectionContract;
use Statamic\Facades\Blink;
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
            app('statamic.eloquent.entries.model')::find($entry->id())->update(['uri' => $entry->uri()]);
        });
    }

    public function all(): IlluminateCollection
    {
        return Blink::once('eloquent-collections', function () {
            return $this->transform(app('statamic.eloquent.collections.model')::all());
        });
    }

    public function find($handle): ?CollectionContract
    {
        return Blink::once("eloquent-collection-{$handle}", function () use ($handle) {
            $model = app('statamic.eloquent.collections.model')::whereHandle($handle)->first();

            return $model ? app(CollectionContract::class)->fromModel($model) : null;
        });
    }

    public function findByHandle($handle): ?CollectionContract
    {
        return $this->find($handle);
    }

    public function save($entry)
    {
        $model = $entry->toModel();
        $model->save();

        Blink::forget("eloquent-collection-{$model->handle}");
        Blink::forget('eloquent-collections');

        $entry->model($model->fresh());
    }

    public function delete($entry)
    {
        $model = $entry->model();
        $model->delete();

        Blink::forget("eloquent-collection-{$model->handle}");
        Blink::forget('eloquent-collections');
    }

    protected function transform($items, $columns = [])
    {
        return IlluminateCollection::make($items)->map(function ($model) {
            return Blink::once("eloquent-collection-{$model->handle}", function () use ($model) {
                return app(CollectionContract::class)::fromModel($model);
            });
        });
    }

    public static function bindings(): array
    {
        return [
            CollectionContract::class => Collection::class,
        ];
    }

    public function updateEntryOrder(CollectionContract $collection, $ids = null)
    {
        $collection->queryEntries()->get()->each->save();
    }
}
