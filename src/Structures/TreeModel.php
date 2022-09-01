<?php

namespace Statamic\Eloquent\Structures;

use Statamic\Eloquent\Database\BaseModel;

class TreeModel extends BaseModel
{
    protected $guarded = [];

    protected $table = 'trees';

    protected $casts = [
        'tree'     => 'json',
        'settings' => 'json',
    ];
}
