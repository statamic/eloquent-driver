<?php

namespace Statamic\Eloquent\Revisions;

use Illuminate\Support\Arr;
use Statamic\Eloquent\Database\BaseModel;

class RevisionModel extends BaseModel
{
    protected $guarded = [];

    protected $table = 'revisions';

    protected $casts = [
        'attributes' => 'json',
    ];
}
