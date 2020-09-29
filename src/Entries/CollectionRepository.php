<?php

namespace Statamic\Eloquent\Entries;

use Illuminate\Support\Facades\Cache;
use Statamic\Stache\Repositories\CollectionRepository as StacheRepository;

class CollectionRepository extends StacheRepository
{
    public function updateEntryUris($collection)
    {
        foreach ($collection->sites() as $site) {
            $this->updateEntryUrisForSite($collection, $site);
        }
    }

    private function updateEntryUrisForSite($collection, $site)
    {
        $key = 'previous-collection-route-'.$collection->id().'-'.$site;
        $route = $collection->route($site);

        if (Cache::get($key) !== $route) {
            $collection
                ->queryEntries()
                ->where('site', $site)
                ->get()->each(function ($entry) {
                    EntryModel::where('id', $entry->id())->update(['uri' => $entry->uri()]);
                });
        }

        Cache::forever($key, $route);
    }
}
