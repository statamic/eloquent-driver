<?php

namespace Statamic\Eloquent\Collections;

use Statamic\Contracts\Entries\Collection as CollectionContract;
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

    public static function bindings(): array
    {
        return [
            CollectionContract::class => Collection::class,
        ];
    }
}
