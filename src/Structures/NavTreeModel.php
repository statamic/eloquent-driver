<?php

namespace Statamic\Eloquent\Structures;

use Illuminate\Database\Eloquent\Model as Eloquent;

class NavTreeModel extends Eloquent
{
    protected $guarded = [];

    protected $table = 'navigation_trees';

    protected $casts = [
        'tree' => 'json',
    ];
}
