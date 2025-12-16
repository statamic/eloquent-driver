<?php

namespace Statamic\Eloquent\Sites;

use Illuminate\Support\Arr;
use Statamic\Eloquent\Database\BaseModel;

class SiteModel extends BaseModel
{
    protected $guarded = [];

    protected $table = 'sites';

    protected $casts = [
        'attributes' => 'json',
    ];

    public function getAttribute($key)
    {
        return Arr::get($this->getAttributeValue('attributes'), $key, parent::getAttribute($key));
    }
}
