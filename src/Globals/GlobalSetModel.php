<?php

namespace Statamic\Eloquent\Globals;

use Illuminate\Support\Arr;
use Statamic\Eloquent\Database\BaseModel;

class GlobalSetModel extends BaseModel
{
    protected $guarded = [];

    protected $table = 'global_sets';

    protected $casts = [
        'settings' => 'json',
    ];

    public function getAttribute($key)
    {
        return Arr::get($this->getAttributeValue('data'), $key, parent::getAttribute($key));
    }
}
