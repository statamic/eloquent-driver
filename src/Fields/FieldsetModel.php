<?php

namespace Statamic\Eloquent\Fields;

use Illuminate\Support\Arr;
use Statamic\Eloquent\Database\BaseModel;

class FieldsetModel extends BaseModel
{
    protected $guarded = [];

    protected $table = 'fieldsets';

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
