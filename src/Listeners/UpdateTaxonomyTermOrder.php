<?php

namespace Statamic\Eloquent\Listeners;

use Statamic\Contracts\Taxonomies\TermRepository as TermRepositoryContract;
use Statamic\Events\TaxonomyTreeSaved;

class UpdateTaxonomyTermOrder
{
    public function handle(TaxonomyTreeSaved $event)
    {
        if (config('statamic.eloquent-driver.terms.driver', 'file') !== 'eloquent') {
            return;
        }

        app(TermRepositoryContract::class)->updateOrders($event->tree->taxonomy());
    }
}
