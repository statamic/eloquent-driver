<?php

namespace Statamic\Eloquent\Blueprints;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class BlueprintModel extends Model
{
    protected $guarded = [];

    protected $table = 'blueprints';

    protected $casts = [
        'data' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getAttribute($key)
    {
        return Arr::get($this->getAttributeValue('data'), $key, parent::getAttribute($key));
    }
}
