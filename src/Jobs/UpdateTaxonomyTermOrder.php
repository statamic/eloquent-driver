<?php

namespace Statamic\Eloquent\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Statamic\Facades\Term;

class UpdateTaxonomyTermOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $termId;

    public function __construct($termId)
    {
        $this->termId = $termId;
    }

    public function handle()
    {
        if ($term = Term::find($this->termId)) {
            $term->save();
        }
    }
}
