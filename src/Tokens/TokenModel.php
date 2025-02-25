<?php

namespace Statamic\Eloquent\Tokens;

use Statamic\Eloquent\Database\BaseModel;

class TokenModel extends BaseModel
{
    protected $guarded = [];

    protected $table = 'tokens';

    protected function casts(): array
    {
        return [
            'data' => 'json',
            'expire_at' => 'datetime',
        ];
    }
}
