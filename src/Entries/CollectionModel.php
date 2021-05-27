<?php

namespace Statamic\Eloquent\Entries;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Arr;

class CollectionModel extends Eloquent
{
    protected $guarded = [];

    protected $table = 'collections';

    protected $casts = [
        'routes' => 'json',
        'inject' => 'json',
        'taxonomies' => 'json',
        'structure' => 'json',
        'revisions' => 'bool',
        'dated' => 'bool',
        'default_publish_state' => 'bool',
        'ampable' => 'bool',
    ];

}
