<?php

namespace Statamic\Eloquent\Collections;

use Statamic\Eloquent\Structures\CollectionStructure;
use Statamic\Entries\Collection as FileEntry;

class Collection extends FileEntry
{
    protected function makeStructureFromContents()
    {
        return (new CollectionStructure)
            ->handle($this->handle())
            ->expectsRoot($this->structureContents['root'] ?? false)
            ->showSlugs($this->structureContents['slugs'] ?? false)
            ->maxDepth($this->structureContents['max_depth'] ?? null);
    }
}
