<?php

namespace Statamic\Eloquent\Structures;

use Statamic\Structures\CollectionStructure as StatamicCollectionStructure;

class CollectionStructure extends StatamicCollectionStructure
{
    public function newTreeInstance()
    {
        return new CollectionTree;
    }
}
