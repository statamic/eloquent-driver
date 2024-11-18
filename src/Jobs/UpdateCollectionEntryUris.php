<?php

namespace Statamic\Eloquent\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Statamic\Facades\Entry;

class UpdateCollectionEntryUris implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(public string $collection) {}

    public function handle()
    {
        Entry::query()
            ->where('collection', $this->collection)
            ->chunk(100, fn ($entries) => $entries->each->save());
    }
}
