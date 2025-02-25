<?php

namespace Statamic\Eloquent\Structures;

use Statamic\Eloquent\Database\BaseModel;

class NavModel extends BaseModel
{
    protected $guarded = [];

    protected $table = 'navigations';

    protected function casts(): array
    {
        return [
            'settings' => 'json',
        ];
    }
}
