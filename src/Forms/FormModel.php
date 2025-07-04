<?php

namespace Statamic\Eloquent\Forms;

use Statamic\Eloquent\Database\BaseModel;

class FormModel extends BaseModel
{
    protected $guarded = [];

    protected $table = 'forms';

    protected function casts(): array
    {
        return [
            'settings' => 'json',
        ];
    }
}
