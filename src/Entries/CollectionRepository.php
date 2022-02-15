<?php

namespace Statamic\Eloquent\Entries;

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
            EntryModel::find($entry->id())->update(['uri' => $entry->uri()]);
        });
    }
}
