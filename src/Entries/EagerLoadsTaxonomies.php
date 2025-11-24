<?php

namespace Statamic\Eloquent\Entries;

use Statamic\Facades\Collection;

trait EagerLoadsTaxonomies
{
    public function scopeWithTaxonomies($query)
    {
        return $query->with('terms');
    }

    public function scopeWithTaxonomiesForCollection($query, $collectionHandle)
    {
        $collection = Collection::find($collectionHandle);
        
        if (!$collection) {
            return $query;
        }

        // Check if collection has taxonomy fields
        $hasTaxonomyFields = $collection->entryBlueprints()
            ->flatMap(fn($blueprint) => $blueprint->fields()->all())
            ->contains(fn($field) => in_array($field->type(), ['taxonomy', 'terms']));

        if ($hasTaxonomyFields) {
            return $query->with('terms');
        }

        return $query;
    }
}
