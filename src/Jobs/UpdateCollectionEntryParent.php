<?php

namespace Statamic\Eloquent\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Statamic\Facades\Entry;

class UpdateCollectionEntryParent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $entryId;

    public function __construct($entryId)
    {
        $this->entryId = $entryId;
    }

    public function handle()
    {
        if ($entry = Entry::find($this->entryId)) {
            $entry->save();
        }
    }
}
