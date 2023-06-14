<?php

namespace Statamic\Eloquent\Listeners;

use Statamic\Eloquent\Collections\CollectionRepository;
use Statamic\Events\CollectionTreeSaved;

class UpdateStructuredEntryOrder
{
    public function handle(CollectionTreeSaved $event)
    {
        $tree = $event->tree;
        $collection = $tree->collection();

        if (config('statamic.eloquent-driver.entries.driver', 'file') !== 'eloquent') {
            return;
        }

        if (config('statamic.eloquent-driver.collections.driver') === 'eloquent') {
            // If the collections are configured to use Eloquent, then the entry
            // order will be updated through the regular event/listener flow.
            return;
        }

        $diff = $tree->diff();

        $ids = array_merge($diff->moved(), $diff->added());

        if (empty($ids)) {
            return;
        }

        app(CollectionRepository::class)->updateEntryOrder($collection, $ids);
    }
}
