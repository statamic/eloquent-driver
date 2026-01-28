<?php

namespace Statamic\Eloquent\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Statamic\Facades\Entry;

class UpdateCollectionEntryOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(public $entryId, public $entryOrder) {}

    public function handle()
    {
        if ($entry = Entry::find($this->entryId)) {
            if ($this->entryOrder) {
                $entry->set('order', $this->entryOrder);
            }

            $entry->save();
        }
    }
}
