<?php

namespace Statamic\Eloquent\Structures;

use Statamic\Eloquent\Database\BaseModel;

class TreeModel extends BaseModel
{
    protected $guarded = [];

    protected $table = 'trees';

    protected function casts(): array
    {
        return [
            'tree'     => 'json',
            'settings' => 'json',
        ];
    }
}
