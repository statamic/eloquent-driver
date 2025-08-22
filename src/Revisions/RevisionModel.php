<?php

namespace Statamic\Eloquent\Revisions;

use Statamic\Eloquent\Database\BaseModel;

class RevisionModel extends BaseModel
{
    protected $guarded = [];

    protected $table = 'revisions';

    protected function casts(): array
    {
        return [
            'attributes' => 'json',
        ];
    }
}
