<?php

namespace Statamic\Eloquent\Tokens;

use Statamic\Eloquent\Database\BaseModel;

class TokenModel extends BaseModel
{
    protected $guarded = [];

    protected $table = 'tokens';

    protected $casts = [
        'data' => 'json',
        'expire_at' => 'datetime',
    ];
}
