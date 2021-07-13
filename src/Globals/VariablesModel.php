<?php

namespace Statamic\Eloquent\Globals;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Arr;

class VariablesModel extends Eloquent
{
    protected $guarded = [];

    protected $table = 'global_set_variables';

    protected $casts = [

    ];

    public function getAttribute($key)
    {
        return Arr::get($this->getAttributeValue('data'), $key, parent::getAttribute($key));
    }
}
