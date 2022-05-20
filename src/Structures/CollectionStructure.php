<?php

namespace Statamic\Eloquent\Structures;

use Statamic\Structures\CollectionStructure as StatamicCollectionStructure;
use Statamic\Eloquent\Structures\CollectionTree;

class CollectionStructure extends StatamicCollectionStructure
{
    public function newTreeInstance()
    {
        return new CollectionTree;
    }
}
