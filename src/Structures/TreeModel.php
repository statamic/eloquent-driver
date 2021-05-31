<?php

namespace Statamic\Eloquent\Structures;

use Illuminate\Database\Eloquent\Model as Eloquent;

class TreeModel extends Eloquent
{
    protected $guarded = [];

    protected $table = 'trees';

    protected $casts = [
        'tree' => 'json',
    ];
}
