<?php

namespace Statamic\Eloquent\Taxonomies;

use Illuminate\Support\Arr;
use Statamic\Eloquent\Database\BaseModel;

class TaxonomyModel extends BaseModel
{
    protected $guarded = [];

    protected $table = 'taxonomies';

    protected $casts = [
        'settings' => 'json',
        'sites'    => 'json',
    ];

    public function getAttribute($key)
    {
        return Arr::get($this->getAttributeValue('settings'), $key, parent::getAttribute($key));
    }
}
