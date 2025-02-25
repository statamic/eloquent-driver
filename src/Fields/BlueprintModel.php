<?php

namespace Statamic\Eloquent\Fields;

use Illuminate\Support\Arr;
use Statamic\Eloquent\Database\BaseModel;

class BlueprintModel extends BaseModel
{
    protected $guarded = [];

    protected $table = 'blueprints';

    protected function casts(): array
    {
        return [
            'data' => 'json',
        ];
    }

    public function getAttribute($key)
    {
        return Arr::get($this->getAttributeValue('data'), $key, parent::getAttribute($key));
    }
}
