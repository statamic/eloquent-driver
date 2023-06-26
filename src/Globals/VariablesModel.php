<?php

namespace Statamic\Eloquent\Globals;

use Illuminate\Support\Arr;
use Statamic\Eloquent\Database\BaseModel;

class VariablesModel extends BaseModel
{
    protected $guarded = [];

    protected $table = 'global_set_variables';

    protected $casts = [
        'data' => 'array',
    ];

    public function getAttribute($key)
    {
        return Arr::get($this->getAttributeValue('data'), $key, parent::getAttribute($key));
    }
}
